<?php
/**
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2018 PayU
 *
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0($module)
{
    return Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', 0) &&
        Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', 0);
}
