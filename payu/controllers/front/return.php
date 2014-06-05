<?php
/**
 * PayU Return from LiveUpdate
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUReturnModuleFrontController extends ModuleFrontController
{

	public function process()
	{
		$payu = new PayU();

		$payu->interpretReturnParameters($_SERVER);

		if (version_compare(_PS_VERSION_, '1.5', 'lt'))
			Tools::redirect('history.php', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
		else
			Tools::redirect('index.php?controller=history&payu_order_error=1', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');

		exit;
	}
}
