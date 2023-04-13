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

function upgrade_module_3_2_11($module)
{
   return
       $module->unregisterHook('ActionGetExtraMailTemplateVars') &&
       $module->registerHook('actionGetExtraMailTemplateVars') &&
       $module->unregisterHook('paymentReturn') &&
       $module->registerHook('displayPaymentReturn') &&
       $module->unregisterHook('backOfficeHeader') &&
       $module->registerHook('displayBackOfficeHeader') &&
       $module->unregisterHook('header') &&
       $module->registerHook('displayHeader');
}
