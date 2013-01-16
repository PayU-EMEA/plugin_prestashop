<?php
/**
 *	ver. 1.8.1
 *	PayU Payment Modules
 *
 *	@copyright  Copyright 2012 by PayU
 *	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *	http://www.payu.com
 *	http://twitter.com/openpayu
 */
if (!defined('_PS_VERSION_'))
	exit;
	
	
class PayU extends PayUAbstract
{

    /**
     * Success order handling
     */

    public function execSuccessOrder($id_cart)
    {
        global $cookie;

        if ($id_cart) {
            $this->updateCustomerData($id_cart);
            $cart = new Cart($id_cart);

            $order = new Order((int)$this->currentOrder);
            $ips = payu_session::existsByCartId($cart->id);
            $payuSession = new payu_session($ips);

            $this->validateOrder($cart->id, Configuration::get('PAYMENT_PAYU_NEW_STATE'), $cart->getOrderTotal(true, Cart::BOTH), 'payu', 'payu.pl cart ID: ' . $cart->id . ', sessionId: ' . $payuSession->sid, null, null, false, $cart->secure_key);
            $this->saveSID($payuSession->sid, (int)$this->currentOrder, 'ORDER_STATUS_PENDING', $cart->id);

            if ((int)$cookie->id_customer > 0) {
                Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_order=' . (int)$this->currentOrder);
            } else {
                Tools::redirectLink(__PS_BASE_URI__ . 'guest-tracking.php?id_order=' . (int)$this->currentOrder);
            }
        }
    }
}