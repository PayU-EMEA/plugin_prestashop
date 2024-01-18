<?php

class PayUPaymentModuleFrontController extends ModuleFrontController
{
    /** @var PayU */
    private $payu;
    private $payuNotification = [];
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
        // for retry payment use grandparent initContent
        if ($this->hasRetryPayment) {
            FrontController::initContent();
        } else {
            parent::initContent();
        }
    }

    public function process()
    {
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance. PHP version:  ' . phpversion(), '');
        $payMethod = Tools::getValue('payMethod', 'pbl');

        if ($payMethod === 'ai' ||
            $payMethod === 'c' ||
            $payMethod === 'blik' ||
            $payMethod === 'dp' ||
            $payMethod === 'dpt' ||
            $payMethod === 'dpp'
        ) {
            $this->pay($payMethod, [], $payMethod);
        }
        elseif ($payMethod === 'transfer') {
            $paymentGateway = Tools::getValue('transferGateway');
            $paymentId = Tools::getValue('payment_id');

            if ($paymentGateway) {
                $this->pay($paymentGateway, [], 'pbl / ' . $paymentGateway);
            } else {
                $this->payuNotification[$payMethod] = $this->module->l('Select a payment channel', 'payment');

                if ($this->hasRetryPayment) {
                    $params = [
                        'id_order' => Tools::getValue('id_order'),
                        'payment_id' => $paymentId
                    ];
                    $this->payuRedirectWithNotifications(
                        $this->context->link->getPageLink('order-detail', null, null, $params)
                    );
                } else {
                    $this->payuRedirectWithNotifications(
                        $this->context->link->getPageLink('order',
                            null,
                            null,
                            [
                                'payment_id' => $paymentId
                            ]
                        )
                    );
                }
            }
        }
        elseif ($payMethod === 'card') {
            $cardToken = Tools::getValue('cardToken');
            $paymentId = Tools::getValue('payment_id');

            if ($cardToken) {
                $this->pay($payMethod, ['cardToken' => $cardToken], $payMethod);
            } else {
                $this->payuNotification[$payMethod] = $this->module->l('Card token is empty', 'payment');

                if ($this->hasRetryPayment) {
                    $params = [
                        'id_order' => Tools::getValue('id_order'),
                        'payment_id' => $paymentId
                    ];
                    $this->payuRedirectWithNotifications(
                        $this->context->link->getPageLink('order-detail', null, null, $params)
                    );
                } else {
                    $this->payuRedirectWithNotifications(
                        $this->context->link->getPageLink('order',
                            null,
                            null,
                            [
                                'payment_id' => $paymentId
                            ]

                        )
                    );
                }
            }
        } else  {
            $this->pay();
        }

        if ($this->hasRetryPayment) {
            $this->payuNotification['error'] = $this->module->l('An error occurred while processing your payment. Please try again or contact the store.', 'payment');
            $params = [
                'id_order' => Tools::getValue('id_order')
            ];

            $this->payuRedirectWithNotifications(
                $this->context->link->getPageLink('order-detail',null,null, $params)
            );
        } else {
            $this->showPaymentError();
        }

    }

    private function showPaymentError()
    {
        $this->context->smarty->assign(
            [
                'image' => $this->payu->getPayuLogo(),
                'total' => Tools::displayPrice($this->order->total_paid, (int)$this->order->id_currency),
                'orderCurrency' => (int)$this->order->id_currency,
                'buttonAction' => $this->context->link->getModuleLink('payu', 'payment', ['id_order' => $this->order->id, 'order_reference' => $this->order->reference]),
                'payuOrderInfo' => $this->module->l('Pay for your order', 'payment') . ' [' . $this->order->reference . ']',
                'payuError' => $this->module->l('An error occurred while processing your payment.', 'payment')
            ]
        );

        $this->setTemplate($this->payu->buildTemplatePath('error'));
    }

    private function pay($payMethod = null, $parameters = [], $method = '')
    {
        if (Tools::getValue('id_order') !== false && Tools::getValue('order_reference') !== false) {
            $order = new Order(Tools::getValue('id_order'));
            $orderTotal = $order->total_paid;
        } else {
            $orderTotal = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        }

        if (!$this->hasRetryPayment) {
            $this->payu->validateOrder(
                $this->context->cart->id,
                (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $orderTotal,
                $this->payu->displayName,
                null,
                [],
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
            $this->postOCR($method);

            SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to ' . $result['redirectUri'], $result['orderId']);


            Tools::redirect($result['redirectUri']);

        } catch (\Exception $e) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'An error occurred while processing  OCR - ' . $e->getMessage(), '');

            if ($this->hasRetryPayment) {
                return ['message' => $this->module->l('An error occurred while processing your payment. Please try again or contact the store.', 'payment')];
            }

            return [
                'firstPayment' => true
            ];
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

    private function postOCR($method)
    {

        if ($this->hasRetryPayment) {
            $history = new OrderHistory();
            $history->id_order = $this->order->id;
            $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_PENDING'), $this->order->id);
            $history->addWithemail(true);
        }
        $orders[] = $this->order;
        foreach ($this->order->getBrother() as $linkedOrder) {
            $orders[] = $linkedOrder;
        }

        $this->payu->addOrdersSessionId(
            $orders,
            OpenPayuOrderStatus::STATUS_NEW,
            $this->payu->payu_order_id,
            $this->payu->getExtOrderId(),
            $method
        );
    }

    public function payuRedirectWithNotifications($notifications)
    {
        $payuNotifications = json_encode($this->payuNotification);

        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['payuNotifications'] = $payuNotifications;
        } elseif (session_status() == PHP_SESSION_NONE) {
            session_start();
            $_SESSION['payuNotifications'] = $payuNotifications;
        } else {
            setcookie('payuNotifications', $payuNotifications);
        }

        return call_user_func_array(['Tools', 'redirect'], func_get_args());
    }

}
