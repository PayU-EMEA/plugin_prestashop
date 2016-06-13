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
    private $payu;

    public $display_column_left = false;

    public function postProcess()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

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

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
    }


    public function initContent()
    {
        parent::initContent();
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance. PHP version:  '.phpversion(), '');

        $this->payu = new PayU();
        $cart = $this->context->cart;
        $products = $cart->getProducts();

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
                if (!$payuConditions ) {
                    $errors[] = $this->module->l('Please accept "Terms of single PayU payment transaction"', 'payment');
                }
                if (count($errors) == 0) {
                    $errors[] = $this->pay($cart, $payMethod);
                }
                
                $this->showPayMethod($payMethod, $payuConditions, $errors);
            } else {
                $this->showPayMethod();
            }
        } else {
            $this->pay($cart);
        }
    }

    private function showPayMethod($payMethod = '', $payuConditions = 1, $errors = array())
    {

        $this->context->smarty->assign(array(
            'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'payMethods' => $this->getPaymethods(Currency::getCurrency($this->context->cart->id_currency)),
            'payMethod' => $payMethod,
            'image' => Media::getMediaPath(_PS_MODULE_DIR_ . $this->payu->name . '/img/payu_logo.png'),
            'conditionUrl' => $this->payu->getPayConditionUrl(),
            'payuConditions' => $payuConditions,
            'payuErrors' => $errors
        ));

        $this->setTemplate('payMethods.tpl');
    }

    private function getPaymethods($currency)
    {
        try {
            $this->payu->initializeOpenPayU($currency['iso_code']);

            $retreive = OpenPayU_Retrieve::payMethods(Language::getIsoById($this->context->language->id));
            if ($retreive->getStatus() == 'SUCCESS') {
                $response = $retreive->getResponse();
                return array(
                    'payByLinks' => $this->moveCardToFirstPosition($response->payByLinks)
                );
            } else {
                return array(
                    'error' => $retreive->getStatus() . ': ' . OpenPayU_Util::statusDesc($retreive->getStatus())
                );
            }

        } catch (OpenPayU_Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }

    private function moveCardToFirstPosition($payMethods)
    {
        foreach ($payMethods as $id => $payMethod) {
            if ($payMethod->value == 'c') {
                $cart = $payMethod;
                unset($payMethods[$id]);
                array_unshift($payMethods, $cart);
                return $payMethods;
            }
        }
        return $payMethods;
    }

    private function pay($cart, $payMethod = null)
    {

        $this->payu->cart = $cart;

        $_SESSION['sessionId'] = md5($this->payu->cart->id . rand() . rand() . rand() . rand());

        $result = $this->payu->orderCreateRequest($payMethod);

        if (!$result['error']) {
            $this->payu->id_cart = $cart->id;
            $this->payu->payu_order_id = $result['orderId'];
            $this->payu->validateOrder(
                $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $cart->getOrderTotal(true, Cart::BOTH), $this->payu->displayName,
                'PayU cart ID: ' . $cart->id . ', orderId: ' . $this->payu->payu_order_id,
                null, (int)$cart->id_currency, false, $cart->secure_key,
                Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
            );
            $this->payu->addOrderSessionId(OpenPayuOrderStatus::STATUS_NEW);
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to summary...', $result['orderId']);
            Tools::redirect($result['redirectUri']);
        } else {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Result is empty: An error occurred while processing your order.', '');
            if ($payMethod !== null) {
                return $this->module->l('An error occurred while processing your order.', 'payment') . ' ' .$result['error'];
            }

            $this->context->smarty->assign(
                array(
                    'message' => $this->module->l('An error occurred while processing your order.', 'payment') . ' ' . $result['error']
                )
            );
            $this->setTemplate('error.tpl');
        }

    }
}
