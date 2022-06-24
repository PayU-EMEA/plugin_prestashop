<?php
/**
 * @author    PayU
 * @copyright Copyright (c) PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
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
