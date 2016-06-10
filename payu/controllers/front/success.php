<?php
/**
 * PayU success
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUSuccessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $payu = new PayU();

        $payu->id_cart = Tools::getValue('id_cart');
        $payu->payu_order_id = $this->context->cookie->__get('payu_order_id');
        $order_payment = $payu->getOrderPaymentBySessionId($payu->payu_order_id);

        if (Tools::getValue('error')) {
            Tools::redirect(
                $this->getRedirectLink($payu->id_cart, $order_payment['id_order'], 'error=' . Tools::getValue('error')),
                __PS_BASE_URI__,
                null,
                'HTTP/1.1 301 Moved Permanently'
            );
        }

        if ($order_payment) {
            $payu->id_order = (int)$order_payment['id_order'];
            $payu->id_cart = (int)$order_payment['id_cart'];

            $payu->updateOrderData();
            Tools::redirect($this->getRedirectLink($payu->id_cart, $order_payment['id_order']), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
        }

        Tools::redirect('index.php?controller=history', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');

    }

    private function getRedirectLink($id_cart, $id_order, $params = null)
    {
        if (Cart::isGuestCartByCartId($id_cart)) {
            $order = new Order($id_order);
            $customer = new Customer((int)$order->id_customer);
            return 'index.php?controller=guest-tracking&id_order=' . $order->reference . '&email=' . urlencode($customer->email) . ($params != null ? '&' . $params : '');;
        } else {
            return 'index.php?controller=order-detail&id_order=' . $id_order . ($params != null ? '&' . $params : '');
        }

    }
}