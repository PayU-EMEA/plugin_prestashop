<?php
/**
 * PayU
 *
 * @author    PayU
 * @copyright Copyright (c) 2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_5_0($module)
{
    $module->registerHook('displayOrderDetail');

    if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'order_payu_payments LIKE "ext_order_id"') == false) {
        Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payu_payments ADD ext_order_id VARCHAR(64) NOT NULL AFTER id_session');
    }

    Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', $module->addNewOrderState('PAYU_PAYMENT_STATUS_CANCELED',
            array('en' => 'PayU payment canceled', 'pl' => 'Płatność PayU anulowana'), true));

    return true;
}
