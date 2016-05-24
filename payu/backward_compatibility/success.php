<?php
/**
 * OpenPayU
 *
 * @copyright  Copyright (c) 2013 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */

include(dirname(__FILE__) . '/../../../config/config.inc.php');
include(dirname(__FILE__) . '/../../../init.php');
include(dirname(__FILE__) . '/../../../header.php');

$payu = new PayU();

$id_cart = Tools::getValue('id_cart');

global $cookie;
$id_payu_session = $cookie->__get('payu_order_id');


if (Tools::getValue('error')) {
    $opc = (bool)Configuration::get('PS_ORDER_PROCESS_TYPE');
    Tools::redirect('order' . ($opc ? '-opc' : '') . '.php?error=' . Tools::getValue('error'), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
}

$order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);
if ($order_payment) {
    $payu->id_order = (int)$order_payment['id_order'];
    $payu->id_cart = (int)$order_payment['id_cart'];

    if (Cart::isGuestCartByCartId($payu->id_cart)) {
        $order = new Order($payu->id_order);
        $customer = new Customer((int)$order->id_customer);
        $redirectLink = 'guest-tracking.php?id_order=' . $order->reference . '&email=' . urlencode($customer->email);
    } else {
        $redirectLink = 'order-detail.php?id_order=' . $payu->id_order;
    }

    $payu->updateOrderData();
    Tools::redirect($redirectLink, __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
}

Tools::redirect('history.php', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');