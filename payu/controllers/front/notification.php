<?php
/**
 * PayU notification
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');

class PayUNotificationModuleFrontController extends ModuleFrontController
{

    public function process()
    {
        $body = Tools::file_get_contents('php://input');
        $data = trim($body);
        $result = OpenPayU_Order::consumeNotification($data);
        $response = $result->getResponse();
        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $response->order->orderId, 'Incoming notification: ');

        if (isset($response->order->orderId)) {
            $payu = new PayU();
            $payu->payu_order_id = $response->order->orderId;

            $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);
            $id_order = (int)$order_payment['id_order'];

            // if order not validated yet
            if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW) {
                $id_order = $this->createOrder($order_payment, $payu, $response);
            }

            if ($this->checkIfPaymentIdIsPresent($response) && $response->order->status == PayU::ORDER_V2_COMPLETED) {
                $this->addPaymentIdToOrder($response, $payu, $id_order);
            }

            if (!empty($id_order)) {
                $payu->id_order = $id_order;
                $payu->updateOrderData($response);
            }

            //the response should be status 200
            header("HTTP/1.1 200 OK");
            exit;
        }

    }

    private function checkIfPaymentIdIsPresent($response)
    {
        if (isset($response->properties) && !empty($response->properties) && is_array($response->properties)) {
            if (isset($response->properties[0]) && isset($response->properties[0]->name) && isset($response->properties[0]->value) && $response->properties[0]->name == 'PAYMENT_ID') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $order_payment
     * @param $payu
     * @param $response
     * @return mixed
     */
    private function createOrder($order_payment, Payu $payu, $response)
    {
        $cart = new Cart($order_payment['id_cart']);

        $payu->validateOrder(
            $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            $cart->getOrderTotal(true, Cart::BOTH), $payu->displayName,
            'PayU cart ID: ' . $cart->id . ', orderId: ' . $payu->payu_order_id,
            null, (int)$cart->id_currency, false, $cart->secure_key,
            Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
        );

        $id_order = $payu->current_order = $payu->currentOrder;

        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Status zamówienia PayU: ' . PayU::PAYMENT_STATUS_NEW, $response->order->orderId);
        $payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
        return $id_order;
    }

    /**
     * @param $response
     * @param $payu
     * @param $id_order
     */
    private function addPaymentIdToOrder($response, Payu $payu, $id_order)
    {
        $payu->payu_payment_id = $response->properties[0]->value;
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'PAYMENT_ID: ' . $payu->payu_payment_id, $payu->payu_order_id);
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Status zamówienia PayU: ' . $response->order->status, $response->order->orderId);
        $payu->addMsgToOrder('payment_id: ' . $payu->payu_payment_id, $id_order);
    }
}
