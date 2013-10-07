<?php

/**
 * @copyright  Copyright (c) 2011-2012 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * OpenPayU Standard Library
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

include_once('openpayu_domain.php');

define('OPENPAYU_LIBRARY', true);
/*
these files are obsolete and will be removed in future.
valid only for SDK 0.x
*/
include_once('OpenPayU/OpenPayUNetwork.php');
include_once('OpenPayU/OpenPayUBase.php');
include_once('OpenPayU/OpenPayU.php');
include_once('OpenPayU/OpenPayUOAuth.php');

/* 
these files are 1.x compatible
*/
include_once('OpenPayU/Result.php');
include_once('OpenPayU/ResultOAuth.php');
include_once('OpenPayU/Configuration.php');
include_once('OpenPayU/Order.php');
include_once('OpenPayU/OAuth.php');

include_once('ePayment/PayuSettings.class.php');
include_once('ePayment/PayuAddress.class.php');
include_once('ePayment/PayuProduct.class.php');
include_once('ePayment/PayuLiveUpdate.class.php');
include_once('ePayment/PayuSignature.class.php');
include_once('ePayment/PayuIDN.class.php');
include_once('ePayment/PayuIRN.class.php');