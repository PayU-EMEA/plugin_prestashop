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
class PayUPaymentModuleFrontController extends ModuleFrontController
{
    private $payu;
    
    public function initContent()
    {
        parent::initContent();
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance. PHP version:  '.phpversion(), '');

        $cart = $this->context->cart;
        $products = $cart->getProducts();

        if (empty($products)) {
            Tools::redirect('index.php?controller=order');
        }

        $this->payu = new PayU();
        $this->payu->cart = $cart;

        $_SESSION['sessionId'] = md5($this->payu->cart->id . rand() . rand() . rand() . rand());

        $result = $this->payu->orderCreateRequest();

        if ($result) {
            $this->payu->id_cart = $cart->id;
            $this->payu->payu_order_id = $result['orderId'];
            $this->payu->validateOrder(
                $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                $cart->getOrderTotal(true, Cart::BOTH), $this->payu->displayName,
                'PayU cart ID: ' . $cart->id . ', orderId: ' . $this->payu->payu_order_id,
                null, (int)$cart->id_currency, false, $cart->secure_key,
                Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
            );
            $this->payu->addOrderSessionId(OpenPayuOrderStatus::STATUS_NEW);
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to summary...', $result['orderId']);
            Tools::redirect($result['redirectUri']);
        } else {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Result is empty: An error occurred while processing your order.', '');
            $this->context->smarty->assign(
                array(
                    'message' => $this->payu->l('An error occurred while processing your order.')
                )
            );
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Result is empty: An error occurred while processing your order.', '');
            $this->setTemplate('error.tpl');
        }
    }
}
