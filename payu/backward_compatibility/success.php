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

$order_payment = $payu->getOrderPaymentByExtOrderId(Tools::getValue('id'));

if ($order_payment) {
    $payu->id_order = $order_payment['id_order'];
    $payu->id_cart = $order_payment['id_cart'];
    $payu->payu_order_id = $order_payment['id_session'];

    $payu->updateOrderData();

    if (Cart::isGuestCartByCartId($payu->id_cart)) {
        $order = new Order($payu->id_order);
        $customer = new Customer((int)$order->id_customer);
        $redirectLink = 'guest-tracking.php?id_order=' . $order->reference . '&email=' . urlencode($customer->email);
    } else {
        $redirectLink = 'order-detail.php?id_order=' . $payu->id_order;
    }

    Tools::redirect($redirectLink, __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
}

Tools::redirect('history.php', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');