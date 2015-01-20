<?php

/**
 * PayU validation
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */
class PayUValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        //$id_session = Tools::getValue('sessionId');
        $id_session = $this->context->cookie->__get('payu_order_id');
        $redirect_uri = Tools::getValue('redirectUri');

        $payu = new PayU();

        $payu->payu_order_id = $id_session;
        $payu->id_cart = $cart->id;
        SimplePayuLogger::addLog('order', __FUNCTION__, 'validation.php ' . $payu->l('Entrance'), $payu->payu_order_id);

        $payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);

        SimplePayuLogger::addLog('order', __FUNCTION__, $payu->l('Process redirect to redirectUrl: ') . $redirect_uri, $payu->payu_order_id);
        Tools::redirect($redirect_uri);
    }
}
