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

function PayU_extractCurrencyCode($data)
{
    $decodeData = json_decode($data);
    return $decodeData->order->currencyCode;
}

function PayU_getPaymentId($response)
{
    if (isset($response->properties)) {
        return PayU_extractPaymentIdFromProperties($response->properties);
    }
    return false;
}

function PayU_extractPaymentIdFromProperties($properties)
{
    if (is_array($properties)) {
        foreach ($properties as $property) {
            if ($property->name == 'PAYMENT_ID') {
                return $property->value;
            }
        }
    }

    return false;
}

ob_clean();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $body = Tools::file_get_contents('php://input');
    $data = trim($body);

    $payu = new PayU();
    $payu->initializeOpenPayU(PayU_extractCurrencyCode($data));

    $result = OpenPayU_Order::consumeNotification($data);
    $response = $result->getResponse();

    if (isset($response->order->orderId)) {
        $payu->payu_order_id = $response->order->orderId;
        $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);

        if ($order_payment) {
            $payu->id_order = (int)$order_payment['id_order'];
            $payu->updateOrderData($response);

            $paymentId = PayU_getPaymentId($response);

            if ($paymentId !== false && $response->order->status == OpenPayuOrderStatus::STATUS_COMPLETED) {
                $payu->addMsgToOrder('payment_id: ' . $payu->payu_payment_id, $id_order);
            }
        }

        //the response should be status 200
        header("HTTP/1.1 200 OK");
    }
}

ob_end_flush();
exit;
