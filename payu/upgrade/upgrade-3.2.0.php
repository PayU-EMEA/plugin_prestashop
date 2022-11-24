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

function upgrade_module_3_2_0($module)
{
   return
       $module->createPayUHistoryTable() &&
       $module->registerHook('ActionGetExtraMailTemplateVars') &&
       Configuration::updateValue('PAYU_PAYMENT_METHODS_GRID', 1) &&
       Configuration::updateValue('PAYU_REPAY', 0);
}
