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
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance. PHP version:  ' . phpversion(), '');
        $payMethod = Tools::getValue('payMethod');
        if (Configuration::get('PAYU_PAYMENT_METHODS_GRID')) {
            $errors = [];

            if (Tools::getValue('payuPay')) {
                $payuConditions = Tools::getValue('payuConditions');
                $cardToken = Tools::getValue('cardToken');
                $payError = [];

                if (!$payMethod) {
                    $errors[] = $this->module->l('Please select a method of payment', 'payment');
                }

                if (!$payuConditions) {
                    $errors[] = $this->module->l('Please accept "Terms of single PayU payment transaction"', 'payment');
                }

                if ($payMethod === 'card' && !$cardToken) {
                    $errors[] = $this->module->l('Card token is empty', 'payment');
                }

                if (count($errors) == 0) {
                    $payError = $this->pay($payMethod, ['cardToken' => $cardToken]);
                    if (!array_key_exists('firstPayment', $payError)) {
                        $errors[] = $payError['message'];
                    }
                }
                if (array_key_exists('firstPayment', $payError)) {
                    $this->showPaymentError();
                } else {
                    if ($payMethod === 'card' && Configuration::get('PAYU_CARD_PAYMENT_WIDGET') == 1) {
                        $cardToken = Tools::getValue('cardToken');
                        if ($cardToken) {
                            $this->showSecureForm($payuConditions, $errors);
                        } else {
                            $this->errors[] = $this->module->l('Card token is empty', 'payment');

                            $this->RedirectWithNotifications(
                                $this->context->link->getPageLink('order',
                                    null,
                                    null
                                )
                            );
                        }

                    } elseif ($payMethod === 'card' && Configuration::get('PAYU_CARD_PAYMENT_WIDGET') !== 1) {
                        $this->pay('c');
                    } else {
                        $this->showPayMethod($payMethod, $payuConditions, $errors);
                    }
                }
            } else {
                if ($payMethod === 'card' && Configuration::get('PAYU_CARD_PAYMENT_WIDGET') == 1) {
                    $this->showSecureForm();

                } elseif ($payMethod === 'transfer' || $payMethod === 'card') {
                    if ($payMethod == 'card') {
                        $paymentGateway = 'c';
                    } else {
                        $paymentGateway = Tools::getValue('transfer_gateway1');
                    }

                    if ($paymentGateway) {
                        $this->pay($paymentGateway);
                    } else {
                        $paymentId = Tools::getValue('payment_id');
                        $this->errors[] = $this->module->l('Select a payment channel', 'payment');

                        $this->payuRedirectWithNotifications(
                            $this->context->link->getPageLink('order',
                                null,
                                null,
                                'payment_id=' . $paymentId
                            )
                        );
                    }
                }
            }
        } else {
            $payType = null;
            if ($payMethod === 'card') {
                $payType = 'c';
                if($cardToken = Tools::getValue('cardToken')){
                    $payError = $this->pay($payMethod, ['cardToken' => $cardToken]);
                    if (!array_key_exists('firstPayment', $payError)) {
                        $errors[] = $payError['message'];
                    }
                }
            } elseif (
                $payMethod === 'ai' ||
                $payMethod === 'c' ||
                $payMethod === 'blik' ||
                $payMethod === 'dp' ||
                $payMethod === 'dpt' ||
                $payMethod === 'dpp'
            ) {
                $payType = $payMethod;
            }
            $this->pay($payType);
            $this->showPaymentError();
        }
    }

    private function showPayMethod($payMethod = '', $payuConditions = 1, $errors = array())
    {
        $this->context->smarty->assign(array(
            'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
            'payMethod' => $payMethod,
            'image' => $this->payu->getPayuLogo(),
            'conditionUrl' => $this->payu->getPayConditionUrl(),
            'payuConditions' => $payuConditions,
            'payuErrors' => $errors
        ));

        $this->context->smarty->assign($this->getShowPayMethodsParameters());
        if(!$errors) {
            $this->setTemplate($this->payu->buildTemplatePath('payMethods'));
        }
        else{
            $params = [
                'id_order' => Tools::getValue('id_order'),
                'error' => 'select-paymethod'
            ];
            $this->errors[] = $this->module->l('Card token is empty', 'payment');
            $this->payuRedirectWithNotifications(
                $this->context->link->getPageLink('order-detail', true, NULL, $params)
            );
        }
    }

    private function showSecureForm($payuConditions = 1, $errors = array())
    {
        $this->context->smarty->assign(array(
            'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
            'secureFormJsTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/secureFormJs.tpl',
            'payCardTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/payuCardForm.tpl',
            'image' => $this->payu->getPayuLogo(),
            'conditionUrl' => $this->payu->getPayConditionUrl(),
            'payuConditions' => $payuConditions,
            'payuErrors' => $errors,
            'jsSdk' => $this->payu->getPayuUrl(Configuration::get('PAYU_SANDBOX') === '1') . 'javascript/sdk'
        ));

        $this->context->smarty->assign($this->getShowPayMethodsParameters());

        $this->setTemplate($this->payu->buildTemplatePath('secureForm'));
    }

    private function showPaymentError()
    {
        $this->context->smarty->assign(
            array(
                'image' => $this->payu->getPayuLogo(),
                'total' => Tools::displayPrice($this->order->total_paid, (int)$this->order->id_currency),
                'orderCurrency' => (int)$this->order->id_currency,
                'buttonAction' => $this->context->link->getModuleLink('payu', 'payment', array('id_order' => $this->order->id, 'order_reference' => $this->order->reference)),
                'payuOrderInfo' => $this->module->l('Pay for your order', 'payment') . ' ' . $this->order->reference,
                'payuError' => $this->module->l('An error occurred while processing your payment.', 'payment')
            )
        );

        $this->setTemplate($this->payu->buildTemplatePath('error'));
    }

    private function pay($payMethod = null, $parameters = [])
    {
        if (Tools::getValue('id_order') !== false && Tools::getValue('order_reference') !== false) {
            $order = new Order(Tools::getValue('id_order'));
            $orderTotal = $order->total_paid;
        } else {
            $orderTotal = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        }
        SimplePayuLogger::addLog('check', __FUNCTION__, $orderTotal, '');

        if (!$this->hasRetryPayment) {
            $this->payu->validateOrder(
                $this->context->cart->id,
                (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $orderTotal,
                $this->payu->displayName,
                null,
                array(),
                (int)$this->context->cart->id_currency, false, $this->context->cart->secure_key,
                Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
            );

            $this->order = new Order($this->payu->currentOrder);
        }

        $this->payu->generateExtOrderId($this->order->id);
        $this->payu->order = $this->order;

        try {
            $result = $this->payu->orderCreateRequestByOrder($orderTotal, $payMethod, $parameters);
            $this->payu->payu_order_id = $result['orderId'];
            $this->postOCR();

            SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to ' . $result['redirectUri'], $result['orderId']);

            Tools::redirect($result['redirectUri']);

        } catch (\Exception $e) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'An error occurred while processing  OCR - ' . $e->getMessage(), '');

            if ($this->hasRetryPayment) {
                return array('message' => $this->module->l('An error occurred while processing your payment. Please try again or contact the store.', 'payment'));
            }

            return array(
                'firstPayment' => true
            );
        }
    }

    private function postProcessPayment()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !count($this->context->cart->getProducts())) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
    }

    private function postProcessRetryPayment()
    {
        $id_order = (int)Tools::getValue('id_order');
        $order_reference = Tools::getValue('order_reference');
        $this->order = new Order($id_order);
        if (!Validate::isLoadedObject($this->order) || $this->order->reference !== $order_reference) {
            Tools::redirect('index.php?controller=history');
        }
        if (!$this->payu->hasRetryPayment($this->order->id, $this->order->current_state)) {
            Tools::redirect('index.php?controller=history');
        }
    }

    private function checkHasModuleActive()
    {
        if (!Module::isEnabled($this->module->name)) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }

        if (!$this->module->active) {
            die($this->module->l('PayU module isn\'t active.', 'payment'));
        }
    }

    private function checkHasRetryPayment()
    {
        $this->hasRetryPayment = Tools::getValue('id_order') !== false && Tools::getValue('order_reference') !== false;
    }

    private function getShowPayMethodsParameters()
    {
        $currency = $this->hasRetryPayment ? (int)$this->order->id_currency : (int)$this->context->cart->id_currency;
        $this->payu->initializeOpenPayU(Currency::getCurrency($currency)['iso_code']);

        $parameters = [
            'posId' => OpenPayU_Configuration::getMerchantPosId(),
            'orderCurrency' => $currency,
            'payMethods' => $this->payu->getPaymethods(Currency::getCurrency($currency)),
            'retryPayment' => $this->hasRetryPayment,
            'lang' => Language::getIsoById($this->context->language->id)
        ];

        if ($this->hasRetryPayment) {
            return $parameters + array(
                    'total' => Tools::displayPrice($this->order->total_paid, $currency),
                    'payuPayAction' => $this->context->link->getModuleLink(
                        'payu',
                        'payment',
                        array('id_order' => $this->order->id, 'order_reference' => $this->order->reference)
                    ),
                    'payuOrderInfo' => $this->module->l('Retry pay for your order', 'payment') . ' ' . $this->order->reference
                );
        } else {
            return $parameters + array(
                    'total' => Tools::displayPrice($this->context->cart->getOrderTotal(true, Cart::BOTH)),
                    'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment'),
                    'payuOrderInfo' => $this->module->l('The total amount of your order is', 'payment')
                );
        }
    }

    private function postOCR()
    {

        if ($this->hasRetryPayment) {
            $history = new OrderHistory();
            $history->id_order = $this->order->id;
            $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_PENDING'), $this->order->id);
            $history->addWithemail(true);
        }

        $orders = $this->module->getAllOrdersByCartId($this->order->id_cart);

        if ($orders) {
            $this->payu->addOrdersSessionId(
                $orders,
                OpenPayuOrderStatus::STATUS_NEW,
                $this->payu->payu_order_id,
                $this->payu->getExtOrderId()
            );
        }
    }

    public function redirectWithNotifications()
    {
        $notifications = json_encode([
            'error' => $this->errors,
            'warning' => $this->warning,
            'success' => $this->success,
            'info' => $this->info,
        ]);
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['notifications'] = $notifications;
        } elseif (session_status() == PHP_SESSION_NONE) {
            session_start();
            $_SESSION['notifications'] = $notifications;
        } else {
            setcookie('notifications', $notifications);
        }
        return call_user_func_array(['Tools', 'redirect'], func_get_args());
    }

    public function payuRedirectWithNotifications()
    {
        $notifications = json_encode([
            'payu_error' => $this->errors,
        ]);

        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['notifications'] = $notifications;
        } elseif (session_status() == PHP_SESSION_NONE) {
            session_start();
            $_SESSION['notifications'] = $notifications;
        } else {
            setcookie('notifications', $notifications);
        }

        return call_user_func_array(['Tools', 'redirect'], func_get_args());
    }
}
