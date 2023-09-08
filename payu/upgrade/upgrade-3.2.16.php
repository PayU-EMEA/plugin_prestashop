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

function upgrade_module_3_2_16($module)
{
    $alter = true;
    if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'order_payu_payments LIKE "method"') == false) {
        $alter = Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payu_payments ADD method VARCHAR(64) NOT NULL AFTER id_session');
    }

    return $alter;
}
