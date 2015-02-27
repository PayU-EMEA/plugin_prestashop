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
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../../../header.php');

global $smarty, $cookie, $link;

$link = new Link();

$products = $cart->getProducts();

if (empty($products))
	Tools::redirect('index.php?controller=order');

$payu = new PayU();
$payu->cart = $cart;

require(_PS_MODULE_DIR_.$payu->name.'/backward_compatibility/backward.php');

$_SESSION['sessionId'] = md5($payu->cart->id.rand().rand().rand().rand());

switch ($payu->getBusinessPartnerSetting('type'))
{
	case PayU::BUSINESS_PARTNER_TYPE_EPAYMENT:
		$result = array('luForm' => $payu->getLuForm($cart));
		$template = 'lu-form.tpl';
		break;
	case PayU::BUSINESS_PARTNER_TYPE_PLATNOSCI:

		$result = $payu->orderCreateRequest();


		if($result){
			$payu->id_cart = $cart->id;
			$payu->payu_order_id = $result['orderId'];
			$payu->validateOrder(
				$cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
				$cart->getOrderTotal(true, Cart::BOTH), $payu->displayName,
				'PayU cart ID: ' . $cart->id . ', orderId: ' . $payu->payu_order_id,
				null, (int)$cart->id_currency, false, $cart->secure_key,
				Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
			);
			$payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);
			Tools::redirect($result['redirectUri'], '');
		}else{
			$this->context->smarty->assign(
				array(
					'message' => $this->payu->l('An error occurred while processing your order.')
				)
			);
			$this->setTemplate('error.tpl');
		}

		break;
	default:
		/*incorrect partner*/
		break;
}

if (!empty($result))
{
	$smarty->assign(
		$result + array(
			'id_customer' => $cookie->id_customer
		)
	);

	$smarty->assign(array('url_address' => $payu->getModuleAddress().'backward_compatibility/validation.php'));

	echo $payu->fetchTemplate($template);
}
else{
	$smarty->assign(
		array(
			'message' => $payu->l('An error occurred while processing your order.')
		)
	);
	echo $payu->fetchTemplate('error.tpl');
}
