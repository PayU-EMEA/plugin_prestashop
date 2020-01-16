<?php
/**
 * PayU success
 *
 * @author    PayU
 * @copyright Copyright (c) PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUSuccessModuleFrontController extends ModuleFrontController
{

    private $order;

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

        $this->order = new Order($payu->id_order);
        $currentState = $this->order->getCurrentStateFull($this->context->language->id);

        $this->context->smarty->assign(array(
            'payuLogo' => $payu->getPayuLogo('payu_logo_small.png'),
            'orderPublicId' => $this->order->getUniqReference(),
            'redirectUrl' => $this->getRedirectLink(),
            'orderStatus' => $currentState['name'],
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn()
        ));

        $this->setTemplate($payu->buildTemplatePath('status'));
    }

    private function getRedirectLink()
    {
        if (Cart::isGuestCartByCartId($this->order->id_cart)) {

            $customer = new Customer((int)$this->order->id_customer);

            return $this->context->link->getPageLink(
                'guest-tracking',
                null,
                $this->context->language->id,
                ['order_reference' => $this->order->reference, 'email' => $customer->email]
            );
        }

        return $this->context->link->getPageLink(
            'order-detail',
            null,
            $this->context->language->id,
            ['id_order' => $this->order->id]
        );
    }

    /**
     * Execute the hook displayPaymentReturn
     */
    private function displayPaymentReturn()
    {
        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayPaymentReturn', $params, $this->module->id);
        }

        return false;
    }

    /**
     * Execute the hook displayOrderConfirmation
     */
    private function displayOrderConfirmation()
    {
        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayOrderConfirmation', $params);
        }

        return false;
    }

    private function displayHook()
    {
        if (Validate::isLoadedObject($this->order)) {
            $currency = new Currency((int) $this->order->id_currency);

            return array(
                'objOrder' => $this->order,
                'order' => $this->order,
                'currencyObj' => $currency,
                'currency' => $currency->sign,
                'total_to_pay' => $this->order->getOrdersTotalPaid()
            );
        }

        return false;
    }


}
