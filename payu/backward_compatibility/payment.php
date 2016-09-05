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
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../../../header.php');

global $smarty, $cookie, $link;

$link = new Link();

$products = $cart->getProducts();

if (empty($products)) {
    Tools::redirect('index.php?controller=order');
}

$payu = new PayU();
$payu->cart = $cart;

require(_PS_MODULE_DIR_.$payu->name.'/backward_compatibility/backward.php');

$payu->generateExtOrderId($payu->cart->id);

$result = $payu->orderCreateRequest();

if ($result) {
    $payu->id_cart = $cart->id;
    $payu->payu_order_id = $result['orderId'];
    $payu->validateOrder(
        $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
        $cart->getOrderTotal(true, Cart::BOTH), $payu->displayName,
        null, array(), (int)$cart->id_currency, false, $cart->secure_key,
        Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
    );

    $payu->addOrderSessionId(OpenPayuOrderStatus::STATUS_NEW, $payu->currentOrder, $cart->id, $payu->payu_order_id);
    Tools::redirect($result['redirectUri'], '');
} else {
    $smarty->assign(
        array(
            'message' => $payu->l('An error occurred while processing your order.')
        )
    );
    echo $payu->fetchTemplate('error.tpl');
}