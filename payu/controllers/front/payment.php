<?php

/**
 * PayU payment
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */
class PayUPaymentModuleFrontController extends ModuleFrontController
{
    private $payu;
    private $returnPagePS1_4 = 'order.php?step=3';
    private $returnPagePS1_6 = 'index.php?controller=order&step=3';

    const TEMPLATE_EPAYMENT = 'order-summary.tpl';
    const TEMPLATE_ERROR = 'error.tpl';

    public function initContent()
    {
        parent::initContent();
        SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php entrance.. PHP version:  '.phpversion(), '');

        $cart = $this->context->cart;
        $products = $cart->getProducts();

        if (empty($products))
            Tools::redirect('index.php?controller=order');

        $this->payu = new PayU();
        $this->payu->cart = $cart;

        $_SESSION['sessionId'] = md5($this->payu->cart->id . rand() . rand() . rand() . rand());

        switch ($this->payu->getBusinessPartnerSetting('type')) {
            case PayU::BUSINESS_PARTNER_TYPE_EPAYMENT:
                $lu_form = $this->payu->getLuForm($cart);
                if (!empty($lu_form)) {
                    $result = array('luForm' => $lu_form);
                    $template = self::TEMPLATE_EPAYMENT;
                }
                break;
            case PayU::BUSINESS_PARTNER_TYPE_PLATNOSCI:
                $result = $this->payu->orderCreateRequest();

                if($result){
                    $this->payu->id_cart = $cart->id;
                    $this->payu->payu_order_id = $result['orderId'];
                    $this->payu->validateOrder(
                        $cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
                        $cart->getOrderTotal(true, Cart::BOTH), $this->payu->displayName,
                        'PayU cart ID: ' . $cart->id . ', orderId: ' . $this->payu->payu_order_id,
                        null, (int)$cart->id_currency, false, $cart->secure_key,
                        Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
                    );
                    $this->payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);
                    SimplePayuLogger::addLog('order', __FUNCTION__, 'Process redirect to summary...', $result['orderId']);
                    Tools::redirect($result['redirectUri']);
                }else{
                    SimplePayuLogger::addLog('order', __FUNCTION__, $this->payu->l('Result is empty: An error occurred while processing your order.'), '');
                    $this->context->smarty->assign(
                        array(
                            'message' => $this->payu->l('An error occurred while processing your order.')
                        )
                    );
                    SimplePayuLogger::addLog('order', __FUNCTION__, $this->payu->l('Result is empty: An error occurred while processing your order.'), '');
                    $this->setTemplate(self::TEMPLATE_ERROR);
                }
                break;
            default:
                //  incorrect partner
                break;
        }

        if (!empty($result)) {
            $this->context->smarty->assign(
                $result + array(
                    'id_customer' => $this->context->cookie->id_customer,
                    'return_page' => $this->getReturnPage()
                )
            );
            $this->setTemplate($template);
        } else {
            $this->context->smarty->assign(
                array(
                    'message' => $this->payu->l('An error occurred while processing your order.')
                )
            );
            $this->setTemplate(self::TEMPLATE_ERROR);
        }
    }

    private function getReturnPage()
    {
        if (version_compare(_PS_VERSION_, '1.5', 'gt')) {
            return $this->returnPagePS1_6;
        }
        return $this->returnPagePS1_4;
    }
}
