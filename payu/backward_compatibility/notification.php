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

include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../../../header.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');

ob_clean();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $body = Tools::file_get_contents('php://input');
    $data = trim($body);
    $result = OpenPayU_Order::consumeNotification($data);
    $response = $result->getResponse();
    SimplePayuLogger::addLog('notification', 'Przychodząca notyfikacja...', $response->order->orderId);
    SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $response->order->orderId);

    if (isset($response->order->orderId)) {
        $payu = new PayU();
        $payu->payu_order_id = $response->order->orderId;

        if(isset($response->properties[0]) && !empty($response->properties[0])){
            if($response->properties[0]->name == 'PAYMENT_ID' && isset($response->properties[0]->value)){
                $payu->payu_payment_id = $response->properties[0]->value;
            }
        }

        $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);
        $id_order = (int)$order_payment['id_order'];
        // if order not validated yet
        if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW) {
            $cart = new Cart($order_payment['id_cart']);

            $payu->validateOrder(
                $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $cart->getOrderTotal(true, Cart::BOTH), $payu->displayName,
                'PayU cart ID: ' . $cart->id . ', orderId: ' . $payu->payu_order_id,
                null, (int)$cart->id_currency, false, $cart->secure_key,
                Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
            );

            $id_order = $payu->current_order = $payu->{'currentOrder'};
            $payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
        }

        if($response->order->status == PayU::ORDER_V2_COMPLETED){
            $payu->addMsgToOrder('payment_id: ' . $payu->payu_payment_id, $id_order);
        }

        if (!empty($id_order)) {
            $payu->id_order = $id_order;
            $payu->updateOrderData($response);
        }

        //the response should be status 200
        header("HTTP/1.1 200 OK");
    }
}
ob_end_flush();
exit;
