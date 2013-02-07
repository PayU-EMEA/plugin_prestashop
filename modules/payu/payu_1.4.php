<?php
/**
 *  ver. 1.9
 *  PayU Payment Modules
 *
 *  @copyright  Copyright 2012 by PayU
 *  @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *  http://www.payu.com
 *  http://twitter.com/openpayu
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

            $ips = payu_session::existsByCartId($cart->id);
            $payuSession = new payu_session($ips);

            $this->saveSID($payuSession->sid, (int)$this->currentOrder, 'ORDER_STATUS_PENDING', $cart->id);

            if ((int)$cookie->id_customer > 0) {
                Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_order=' . (int)$payuSession->id_order);
            } else {
                Tools::redirectLink(__PS_BASE_URI__ . 'guest-tracking.php?id_order=' . (int)$payuSession->id_order);
            }
        }
    }

    public function execPayment($cart)
    {
        global $smarty, $_SESSION, $cookie;

        $carriers = array();

        $products = $cart->getProducts();
        if (empty($products))
            Tools::redirect('order.php');

        if ((int)$cookie->id_customer > 0) {
            $customer = new Customer((int)($cookie->id_customer));
            $address = new Address((int)($cart->id_address_delivery));
            $id_zone = Address::getZoneById((int)($address->id));
            $carriers = Carrier::getCarriersForOrder($id_zone, $customer->getGroups());
        } else {
            $carriers = Carrier::getCarriers((int)($cart->id_lang), true);
        }

        $_SESSION['sessionId'] = $cart->id . '-' . md5(rand() . rand() . rand() . rand());

        $result = $this->orderCreateRequest($cart, $carriers);

        if (!empty($result)) {
            $this->validateOrder($cart->id, Configuration::get('PAYMENT_PAYU_NEW_STATE'), $cart->getOrderTotal(true, Cart::BOTH), 'payu', 'payu.pl cart ID: ' . $cart->id . ', sessionId: ' . $_SESSION['sessionId'], null, null, false, $cart->secure_key);

            $smarty->assign($result + array('id_customer' => $cookie->id_customer));
            return $this->fetchTemplate('/views/templates/front/', 'order-summary');
        } else {
            $smarty->assign(array('message' => $this->l('An error occurred while processing your order.')));
            return $this->fetchTemplate('/views/templates/front/', 'error');
        }
    }
}