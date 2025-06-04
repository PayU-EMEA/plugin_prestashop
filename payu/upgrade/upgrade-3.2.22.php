<?php
/**
 * @author    PayU
 * @copyright Copyright (c) PayU
 *
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_22($module)
{
    $updatedWidgetToggles = true;
    if (Configuration::get('PAYU_PROMOTE_CREDIT') === '0') {
        $updatedWidgetToggles = Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', 0)
            && Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', 0)
            && Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', 0);
    }
    return Configuration::updateValue('PAYU_SEPARATE_TWISTO_SLICE', 0)
        && Configuration::updateValue('PAYU_SEPARATE_INSTALLMENTS', Configuration::get('PAYU_PROMOTE_CREDIT'))
        && $updatedWidgetToggles
        && Configuration::deleteByName('PAYU_PROMOTE_CREDIT');
}
