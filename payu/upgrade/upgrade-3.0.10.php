<?php
/**
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_10($module)
{
    return Configuration::updateValue('PAYU_PROMOTE_CREDIT', 1) &&
        Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', 1) &&
        Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', 1) &&
        Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', 1) &&
        $module->registerHook('displayOrderDetail') &&
        $module->registerHook('displayProductPriceBlock') &&
        $module->registerHook('displayCheckoutSubtotalDetails') &&
        $module->registerHook('displayCheckoutSummaryTop');
}
