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
        parent::initContent();

        $payu = new PayU();

        $order_payment = $payu->getOrderPaymentByExtOrderId(Tools::getValue('id'));

        if (!$order_payment) {
            Tools::redirect('index.php?controller=history', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
        }


        $payu->id_order = $order_payment['id_order'];
        $payu->id_cart = $order_payment['id_cart'];
        $payu->payu_order_id = $order_payment['id_session'];

        $payu->updateOrderData();

        $order = new Order($payu->id_order);
        $currentState = $order->getCurrentStateFull($this->context->language->id);

        $this->context->smarty->assign(array(
            'payuLogo' => $payu->getPayuLogo(),
            'orderPublicId' => $order->getUniqReference(),
            'redirectUrl' => $this->getRedirectLink($payu->id_cart, $payu->id_order),
            'orderStatus' => $currentState['name'],
        ));

        $this->setTemplate($payu->buildTemplatePath('status', 'front'));

    }

    private function getRedirectLink($id_cart, $id_order)
    {
        if (Cart::isGuestCartByCartId($id_cart)) {
            $order = new Order($id_order);
            $customer = new Customer((int)$order->id_customer);
            return 'index.php?controller=guest-tracking&id_order=' . $order->reference . '&email=' . urlencode($customer->email);
        } else {
            return 'index.php?controller=order-detail&id_order=' . $id_order;
        }

    }
}