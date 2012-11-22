<?php

if (!defined('_PS_VERSION_'))
    exit;


class PayU extends PayUAbstract
{
    /**
     * Success order handling
     */
    public function execSuccessOrder($id_cart)
    {
        if ($id_cart) {
            $this->updateCustomerData($id_cart);
            $cart = new Cart($id_cart);

            $order = new Order((int)$cart->id);
            $ips = payu_session::existsByCartId($cart->id);
            $payuSession = new payu_session($ips);

            $this->validateOrder($cart->id, Configuration::get('PAYMENT_PAYU_NEW_STATE'), $cart->getOrderTotal(true, Cart::BOTH), 'payu', 'payu.pl cart ID: ' . $cart->id . ', sessionId: ' . $payuSession->sid, null, (int)$cart->id_currency, false, $cart->secure_key, Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null);

            $this->saveSID($payuSession->sid, (int)$this->currentOrder, 'ORDER_STATUS_PENDING', $cart->id);
            
            if ((int)Context::getContext()->cookie->id_customer > 0 && !Context::getContext()->customer->is_guest) {
                Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_order=' . (int)$this->currentOrder);
            } else {
                Tools::redirectLink(__PS_BASE_URI__ . 'guest-tracking.php?id_order=' . (int)$this->currentOrder);
            }
        }
    }

    /**
     * Hook add stylesheet
     */
    public function hookHeader()
    {
        $this->context->smarty->assign(array('base_uri' => __PS_BASE_URI__, 'id_cart' => (int)$this->context->cart->id));
        $this->context->controller->addCSS(_MODULE_DIR_ . $this->name . '/css/payu.css');
    }
}