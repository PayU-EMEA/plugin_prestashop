<?php

/**
 * OpenPayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */

class PayUPaymentModuleFrontController extends ModuleFrontController
{
    /** @var PayU */
    private $payu;

    public $display_column_left = false;

    private $hasRetryPayment;

    private $order = null;
    private $payuOrderCart = null;

    public function postProcess()
    {
        $this->checkHasModuleActive();
        $this->checkHasRetryPayment();

        $this->payu = new PayU();

        if ($this->hasRetryPayment) {
            $this->postProcessRetryPayment();
        } else {
            $this->postProcessPayment();
        }

    }


    public function initContent()
    {
        parent::initContent();
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance. PHP version:  '.phpversion(), '');

        $products = $this->getProducts();
        if (empty($products)) {
            Tools::redirect('index.php?controller=order');
        }

        if (Configuration::get('PAYU_RETRIEVE')) {
            if (Tools::getValue('payuPay')) {
                $payMethod = Tools::getValue('payMethod');

                $payuConditions = Tools::getValue('payuConditions');
                $errors = array();
                if (!$payMethod) {
                    $errors[] = $this->module->l('Please select a method of payment', 'payment');
                }
                if (!$payuConditions) {
                    $errors[] = $this->module->l('Please accept "Terms of single PayU payment transaction"', 'payment');
                }
                if (count($errors) == 0) {
                    $errors[] = $this->pay($payMethod);
                }
                
                $this->showPayMethod($payMethod, $payuConditions, $errors);
            } else {
                $this->showPayMethod();
            }
        } else {
            $this->pay();
        }
    }

    private function showPayMethod($payMethod = '', $payuConditions = 1, $errors = array())
    {
        $this->context->smarty->assign(array(
            'payMethod' => $payMethod,
            'image' => $this->payu->getPayuLogo(),
            'conditionUrl' => $this->payu->getPayConditionUrl(),
            'payuConditions' => $payuConditions,
            'payuErrors' => $errors
        ));

        $this->context->smarty->assign($this->getShowPayMethodsParameters());

        $this->setTemplate($this->payu->buildTemplatePath('payMethods', 'front'));
    }


    private function pay($payMethod = null)
    {
        $this->payu->generateExtOrderId($this->getCartId());

        if ($this->hasRetryPayment) {
            $this->payu->order = $this->order;
            $result = $this->payu->orderCreateRequestByOrder($payMethod);
        } else {
            $this->payu->cart = $this->payuOrderCart;
            $result = $this->payu->orderCreateRequest($payMethod);
        }

        if (!array_key_exists('error', $result)) {
            $this->payu->payu_order_id = $result['orderId'];
            $this->postOCR();

            SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to '.($payMethod ? 'bank or card form' : 'summary').'...', $result['orderId']);
            Tools::redirect($result['redirectUri']);
        } else {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Result is empty: An error occurred while processing your order.', '');
            if ($payMethod !== null) {
                return $this->module->l('An error occurred while processing your order.', 'payment') . ' ' .$result['error'];
            }

            $this->context->smarty->assign(
                array(
                    'image' => $this->payu->getPayuLogo(),
                    'total' => Tools::displayPrice($this->context->cart->getOrderTotal(true, Cart::BOTH)),
                    'payuOrderInfo' => $this->module->l('The total amount of your order is', 'payment'),
                    'message' => $this->module->l('An error occurred while processing your order.', 'payment') . ' ' . $result['error']
                )
            );

            $this->setTemplate($this->payu->buildTemplatePath('error', 'front'));
        }

    }

    private function postProcessPayment()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

    }

    private function postProcessRetryPayment()
    {
        if (!($id_order = (int)Tools::getValue('id_order')) || !Validate::isUnsignedId($id_order)) {
            Tools::redirect('index.php?controller=history');
        }

        $this->order = new Order($id_order);

        if (!Validate::isLoadedObject($this->order) || $this->order->id_customer != $this->context->customer->id) {
            Tools::redirect('index.php?controller=history');
        }

        if (!$this->payu->hasRetryPayment($this->order->id, $this->order->current_state)) {
            Tools::redirect('index.php?controller=history');
        }

    }

    private function checkHasModuleActive()
    {

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'payu') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }

        if (!$this->module->active) {
            die($this->module->l('PayU module isn\'t active.', 'payment'));
        }

    }

    private function checkHasRetryPayment()
    {
        $this->hasRetryPayment = Tools::getValue('id_order') !== false ? true : false;
    }

    private function getProducts()
    {
        if ($this->hasRetryPayment) {
            return $this->order->getProducts();
        } else {
            $this->payuOrderCart = $this->context->cart;
            return $this->payuOrderCart->getProducts();
        }
    }

    private function getShowPayMethodsParameters()
    {
        if ($this->hasRetryPayment) {
            return array(
                'total' => Tools::displayPrice($this->order->total_paid, (int)$this->order->id_currency),
                'orderCurrency' => (int)$this->order->id_currency,
                'payMethods' => $this->payu->getPaymethods(Currency::getCurrency($this->order->id_currency)),
                'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment', array('id_order' => $this->order->id)),
                'payuOrderInfo' => $this->module->l('Retry pay for your order', 'payment').' '.$this->order->reference
            );
        } else {
            return array(
                'total' => Tools::displayPrice($this->context->cart->getOrderTotal(true, Cart::BOTH)),
                'orderCurrency' => (int)$this->payuOrderCart->id_currency,
                'payMethods' => $this->payu->getPaymethods(Currency::getCurrency($this->payuOrderCart->id_currency)),
                'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment'),
                'payuOrderInfo' => $this->module->l('The total amount of your order is', 'payment')
            ) ;
        }
    }

    private function getCartId()
    {
        return $this->hasRetryPayment ? $this->order->id_cart : $this->payuOrderCart->id;
    }

    private function postOCR()
    {
        if ($this->hasRetryPayment) {

            $history = new OrderHistory();
            $history->id_order = $this->order->id;
            $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_PENDING'), $this->order->id);
            $history->addWithemail(true);

            $this->payu->addOrderSessionId(OpenPayuOrderStatus::STATUS_NEW, $this->order->id, $this->order->id_cart, $this->payu->payu_order_id, $this->payu->getExtOrderId());
        } else {

            $this->payu->validateOrder(
                $this->payuOrderCart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $this->payuOrderCart->getOrderTotal(true, Cart::BOTH), $this->payu->displayName,
                null, array(), (int)$this->payuOrderCart->id_currency, false, $this->payuOrderCart->secure_key,
                Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
            );

            $this->payu->addOrderSessionId(OpenPayuOrderStatus::STATUS_NEW, $this->payu->currentOrder, $this->payuOrderCart->id, $this->payu->payu_order_id, $this->payu->getExtOrderId());
        }

    }

}
