<?php
/**
 * PayU notification
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2016 PayU
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
        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $response->order->orderId, 'Incoming notification: ');

        if (isset($response->order->orderId)) {
            $payu->payu_order_id = $response->order->orderId;

            $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);

            if ($order_payment) {
                $payu->id_order = (int)$order_payment['id_order'];
                $payu->updateOrderData($response);

                $paymentId = $this->getPaymentId($response);

                if ($paymentId !== false && $response->order->status == OpenPayuOrderStatus::STATUS_COMPLETED) {
                    $this->addPaymentIdToOrder($payu, $paymentId);
                }
            }

            //the response should be status 200
            header("HTTP/1.1 200 OK");
            exit;
        }
    }

    /**
     * @param Payu $payu
     * @param $paymentId
     */
    private function addPaymentIdToOrder(Payu $payu, $paymentId)
    {
        $payu->payu_payment_id = $paymentId;
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'PAYMENT_ID: ' . $payu->payu_payment_id, $payu->payu_order_id);
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Status zamówienia PayU: ' . OpenPayuOrderStatus::STATUS_COMPLETED, $payu->payu_order_id);

        if (version_compare(_PS_VERSION_, '1.5', 'ge')) {
            $order = new Order($payu->id_order);

            $payment = $order->getOrderPaymentCollection();
            $payments = $payment->getAll();
            $payments[$payment->count() - 1]->transaction_id = $payu->payu_payment_id;
            $payments[$payment->count() - 1]->update();
        } else {
            $payu->addMsgToOrder('payment_id: ' . $payu->payu_payment_id, $payu->id_order);
        }
    }

    /**
     * @param $response
     * @return bool | string
     */
    private function getPaymentId($response)
    {
        if (isset($response->properties)) {
            return $this->extractPaymentIdFromProperties($response->properties);
        }
        return false;
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

    /**
     * @param array $properties
     * @return bool | string
     */
    private function extractPaymentIdFromProperties($properties)
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
}
