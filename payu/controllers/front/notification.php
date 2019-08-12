<?php
/**
 * PayU notification
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */

include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');

class PayUNotificationModuleFrontController extends ModuleFrontController
{

    public function process()
    {
        $body = Tools::file_get_contents('php://input');
        $data = trim($body);
        $payu = new PayU();
        $payu->initializeOpenPayU($this->extractCurrencyCode($data));

        try {
            $result = OpenPayU_Order::consumeNotification($data);

        } catch (OpenPayU_Exception $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            die($e->getMessage());
        }

        $response = $result->getResponse();
        if (property_exists($response, 'refund')) {
            die('Refund notification - ignore');
        }

        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $response->order->orderId, 'Incoming notification: ');

        if (isset($response->order->orderId)) {
            $payu->payu_order_id = $response->order->orderId;

            $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);

            if ($order_payment) {
                $payu->id_order = (int)$order_payment['id_order'];
                $payu->updateOrderData($response);
            }

            //the response should be status 200
            header("HTTP/1.1 200 OK");
            exit;
        }
    }

    /**
     * @param string $data
     * @return string
     */
    private function extractCurrencyCode($data)
    {
        $decodeData = json_decode($data);
        return $decodeData->order->currencyCode;
    }
}
