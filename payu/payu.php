<?php
/**
 * PayU module
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

if (!defined('_PS_VERSION_'))
    exit;

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');


class PayU extends PaymentModule
{
    /**
     * PayU - payment statuses
     *
     * @var string
     */
    const PAYMENT_STATUS_NEW = 'PAYMENT_STATUS_NEW';
    const PAYMENT_STATUS_CANCEL = 'PAYMENT_STATUS_CANCEL';
    const PAYMENT_STATUS_REJECT = 'PAYMENT_STATUS_REJECT';
    const PAYMENT_STATUS_INIT = 'PAYMENT_STATUS_INIT';
    const PAYMENT_STATUS_SENT = 'PAYMENT_STATUS_SENT';
    const PAYMENT_STATUS_NOAUTH = 'PAYMENT_STATUS_NOAUTH';
    const PAYMENT_STATUS_REJECT_DONE = 'PAYMENT_STATUS_REJECT_DONE';
    const PAYMENT_STATUS_END = 'PAYMENT_STATUS_END';
    const PAYMENT_STATUS_ERROR = 'PAYMENT_STATUS_ERROR';

    /**
     * PayU - order statuses
     *
     * @var string
     */
    const ORDER_STATUS_PENDING = 'ORDER_STATUS_PENDING';
    const ORDER_STATUS_SENT = 'ORDER_STATUS_SENT';
    const ORDER_STATUS_COMPLETE = 'ORDER_STATUS_COMPLETE';
    const ORDER_STATUS_CANCEL = 'ORDER_STATUS_CANCEL';
    const ORDER_STATUS_REJECT = 'ORDER_STATUS_REJECT';

    /**
     * PayU - order statuses SDK v2
     *
     * @var string
     */
    const ORDER_V2_NEW = 'NEW';
    const ORDER_V2_PENDING = 'PENDING';
    const ORDER_V2_CANCELED = 'CANCELED';
    const ORDER_V2_REJECTED = 'REJECTED';
    const ORDER_V2_COMPLETED = 'COMPLETED';
    const ORDER_V2_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';

    const BUSINESS_PARTNER_TYPE_EPAYMENT = 'epayment';
    const BUSINESS_PARTNER_TYPE_PLATNOSCI = 'platnosci';

    const PAY_BUTTON = 'https://static.payu.com/{lang}/standard/partners/buttons/payu_account_button_01.png';

    public $cart = null;
    public $id_cart = null;
    public $order = null;
    public $payu_order_id = '';
    public $id_order = null;
    public $payu_payment_id = null;
    public $status_completed = false;

    /**
     *
     */
    public function __construct()
    {
        $this->name = 'payu';
        $this->tab = 'payments_gateways';
        $this->version = '2.2.3-DEV';
        $this->author = 'PayU';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.4.4', 'max' => '1.6');

        $this->currencies = true;
        $this->currencies_mode = 'radio';
        $this->is_eu_compatible = 1;

        parent::__construct();

        $this->displayName = $this->l('PayU');
        $this->description = $this->l('Accepts payments by PayU');

        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');

        if (version_compare(_PS_VERSION_, '1.5', 'lt'))
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');

        $this->initializeOpenPayU();

        if (!Configuration::get('PAYU_PAYMENT_PLATFORM'))
            $this->warning = ('Module is not configured.');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (
            function_exists('curl_version') &&
            parent::install() &&
            in_array('curl', get_loaded_extensions()) &&
            $this->createInitialDbTable() &&
            $this->createPaymentTable() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('displayPaymentEU') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('adminOrder') &&
            Configuration::updateValue('PAYU_POS_ID', '') &&
            Configuration::updateValue('PAYU_POS_AUTH_KEY', '') &&
            Configuration::updateValue('PAYU_CLIENT_SECRET', '') &&
            Configuration::updateValue('PAYU_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYU_EPAYMENT_MERCHANT', '') &&
            Configuration::updateValue('PAYU_EPAYMENT_SECRET_KEY', '') &&
            Configuration::updateValue('PAYU_EPAYMENT_IPN', '1') &&
            Configuration::updateValue('PAYU_EPAYMENT_IDN', '1') &&
            Configuration::updateValue('PAYU_EPAYMENT_IRN', '1') &&
            Configuration::updateValue('PAYU_SELF_RETURN', 1) &&
            Configuration::updateValue('PAYU_VALIDITY_TIME', 1440) &&
            Configuration::updateValue('PAYU_ONE_STEP_CHECKOUT', 1) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', $this->addNewOrderState('PAYU_PAYMENT_STATUS_PENDING',
                array('en' => 'PayU payment started', 'pl' => 'Płatność PayU rozpoczęta', 'ro' => 'PayU payment started',
                    'ru' => 'PayU payment started', 'ua' => 'PayU payment started', 'hu' => 'PayU payment started',
                    'tr' => 'PayU payment started'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', $this->addNewOrderState('PAYU_PAYMENT_STATUS_SENT',
                array('en' => 'PayU payment awaits for reception', 'pl' => 'Płatność PayU oczekuje na odbiór',
                    'ro' => 'PayU payment awaits for reception', 'ru' => 'PayU payment awaits for reception',
                    'ua' => 'PayU payment awaits for reception', 'hu' => 'PayU payment awaits for reception',
                    'tr' => 'PayU payment awaits for reception'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', 2) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', 6) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_REJECTED', 7)
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('PAYU_PAYMENT_PLATFORM') ||
            !Configuration::deleteByName('PAYU_NAME') ||
            !Configuration::deleteByName('PAYU_POS_ID') ||
            !Configuration::deleteByName('PAYU_POS_AUTH_KEY') ||
            !Configuration::deleteByName('PAYU_CLIENT_SECRET') ||
            !Configuration::deleteByName('PAYU_SIGNATURE_KEY') ||
            !Configuration::deleteByName('PAYU_EPAYMENT_MERCHANT') ||
            !Configuration::deleteByName('PAYU_EPAYMENT_SECRET_KEY') ||
            !Configuration::deleteByName('PAYU_EPAYMENT_IPN') ||
            !Configuration::deleteByName('PAYU_EPAYMENT_IDN') ||
            !Configuration::deleteByName('PAYU_EPAYMENT_IRN') ||
            !Configuration::deleteByName('PAYU_SELF_RETURN') ||
            !Configuration::deleteByName('PAYU_VALIDITY_TIME') ||
            !Configuration::deleteByName('PAYU_ONE_STEP_CHECKOUT')
        )
            return false;

        return true;
    }

    /**
     * @param $state
     * @param $names
     *
     * @return bool
     */
    private function addNewOrderState($state, $names)
    {
        if (!(Validate::isInt(Configuration::get($state)) AND Validate::isLoadedObject($order_state = new OrderState(Configuration::get($state))))) {
            $order_state = new OrderState();

            if (!empty($names)) {
                foreach ($names as $code => $name) {
                    $order_state->name[Language::getIdByIso($code)] = $name;
                }
            }

            $order_state->send_email = false;
            $order_state->invoice = false;
            $order_state->unremovable = true;
            $order_state->color = '#00AEEF';
            $order_state->module_name = 'payu';

            if (!$order_state->add() || !Configuration::updateValue($state, $order_state->id)) {
                return false;
            }

            copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');

        }

        return $order_state->id;
    }

    /**
     *
     */
    protected function initializeOpenPayU()
    {
        OpenPayU_Configuration::setApiVersion(2);
        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setMerchantPosId(Configuration::get('PAYU_POS_ID'));
        OpenPayU_Configuration::setSignatureKey(Configuration::get('PAYU_SIGNATURE_KEY'));
        OpenPayU_Configuration::setSender('Prestashop ver ' . _PS_VERSION_ . '/Plugin ver ' . $this->version);
    }

    /**
     * @return bool
     */
    private function createInitialDbTable()
    {
        return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_payu_payments` (
					`id_payu_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`id_order` INT(10) UNSIGNED NOT NULL,
					`id_cart` INT(10) UNSIGNED NOT NULL,
					`id_session` varchar(64) NOT NULL,
					`status` varchar(64) NOT NULL,
					`create_at` datetime,
					`update_at` datetime
				)');
    }


    /**
     * @return mixed
     */
    private function createPaymentTable()
    {
        return Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payu_payments` (
				`id_payu_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`id_order` INT UNSIGNED NOT NULL,
				`id_payu_transaction` INT UNSIGNED NOT NULL,
				`payu_amount` double,
				`payu_currency` varchar(3),
				`amount` double,
				`currency` varchar(3),
				`create_at` datetime,
				`update_at` datetime,
				 KEY `id_order` (`id_order`)
			);
		');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = null;
        $errors = array();

        if (Tools::isSubmit('submit' . $this->name)) {
            if (!Configuration::updateValue('PAYU_PAYMENT_PLATFORM', Tools::getValue('PAYU_PAYMENT_PLATFORM')) ||
                !Configuration::updateValue('PAYU_SELF_RETURN', (int)Tools::getValue('PAYU_SELF_RETURN')) ||
                !Configuration::updateValue('PAYU_VALIDITY_TIME', Tools::getValue('PAYU_VALIDITY_TIME')) ||
                !Configuration::updateValue('PAYU_ONE_STEP_CHECKOUT', (int)Tools::getValue('PAYU_ONE_STEP_CHECKOUT')) ||
                !Configuration::updateValue('PAYU_POS_ID', Tools::getValue('PAYU_POS_ID')) ||
                !Configuration::updateValue('PAYU_POS_AUTH_KEY', Tools::getValue('PAYU_POS_AUTH_KEY')) ||
                !Configuration::updateValue('PAYU_CLIENT_ID', Tools::getValue('PAYU_CLIENT_ID')) ||
                !Configuration::updateValue('PAYU_CLIENT_SECRET', Tools::getValue('PAYU_CLIENT_SECRET')) ||
                !Configuration::updateValue('PAYU_SIGNATURE_KEY', Tools::getValue('PAYU_SIGNATURE_KEY')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', (int)Tools::getValue('PAYU_PAYMENT_STATUS_PENDING')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', (int)Tools::getValue('PAYU_PAYMENT_STATUS_SENT')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_COMPLETED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_CANCELED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_REJECTED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_REJECTED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_BUTTON', Tools::getValue('PAYU_PAYMENT_BUTTON')) ||

                !Configuration::updateValue('PAYU_EPAYMENT_MERCHANT', Tools::getValue('PAYU_EPAYMENT_MERCHANT')) ||
                !Configuration::updateValue('PAYU_EPAYMENT_SECRET_KEY', Tools::getValue('PAYU_EPAYMENT_SECRET_KEY')) ||
                !Configuration::updateValue('PAYU_EPAYMENT_IPN', (int)Tools::getValue('PAYU_EPAYMENT_IPN')) ||
                !Configuration::updateValue('PAYU_EPAYMENT_IDN', (int)Tools::getValue('PAYU_EPAYMENT_IDN')) ||
                !Configuration::updateValue('PAYU_EPAYMENT_IRN', (int)Tools::getValue('PAYU_EPAYMENT_IRN'))
            )
                $errors[] = $this->l('Can not save configuration');

            if (!empty($errors))
                foreach ($errors as $error) $output .= $this->displayError($error);
            else
                $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->displayForm();
    }

    /**
     * @return mixed
     */
    public function displayForm()
    {
        // Load current value
        $this->context->smarty->assign(array(
            'PAYU_PAYMENT_PLATFORM_EPAYMENT' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
            'PAYU_PAYMENT_PLATFORM_PLATNOSCI' => self::BUSINESS_PARTNER_TYPE_PLATNOSCI,
            'PAYU_PAYMENT_PLATFORM' => Configuration::get('PAYU_PAYMENT_PLATFORM'),
            'PAYU_PAYMENT_PLATFORM_OPTIONS' => $this->getBusinessPartnersList(),
            'PAYU_SELF_RETURN' => Configuration::get('PAYU_SELF_RETURN'),
            'PAYU_SELF_RETURN_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Yes')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('No')
                )
            ),
            'PAYU_VALIDITY_TIME' => Configuration::get('PAYU_VALIDITY_TIME'),
            'PAYU_VALIDITY_TIME_OPTIONS' => $this->getValidityTimeList(),
            'PAYU_ONE_STEP_CHECKOUT' => Configuration::get('PAYU_ONE_STEP_CHECKOUT'),
            'PAYU_ONE_STEP_CHECKOUT_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Yes')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('No')
                )
            ),
            'PAYU_POS_ID' => Configuration::get('PAYU_POS_ID'),
            'PAYU_POS_AUTH_KEY' => Configuration::get('PAYU_POS_AUTH_KEY'),
            'PAYU_CLIENT_ID' => Configuration::get('PAYU_CLIENT_ID'),
            'PAYU_CLIENT_SECRET' => Configuration::get('PAYU_CLIENT_SECRET'),
            'PAYU_SIGNATURE_KEY' => Configuration::get('PAYU_SIGNATURE_KEY'),
            'PAYU_EPAYMENT_MERCHANT' => Configuration::get('PAYU_EPAYMENT_MERCHANT'),
            'PAYU_EPAYMENT_SECRET_KEY' => Configuration::get('PAYU_EPAYMENT_SECRET_KEY'),
            'PAYU_EPAYMENT_IPN' => Configuration::get('PAYU_EPAYMENT_IPN'),
            'PAYU_EPAYMENT_IPN_URL' => version_compare(_PS_VERSION_, '1.5', 'lt') ?
                $this->getModuleAddress() . 'backward_compatibility/ipn.php' :
                Context::getContext()->link->getModuleLink('payu', 'ipn'),
            'PAYU_EPAYMENT_IPN_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Enabled')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('Disabled')
                )
            ),
            'PAYU_EPAYMENT_IDN' => Configuration::get('PAYU_EPAYMENT_IDN'),
            'PAYU_EPAYMENT_IDN_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Enabled')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('Disabled')
                )
            ),
            'PAYU_EPAYMENT_IRN' => Configuration::get('PAYU_EPAYMENT_IRN'),
            'PAYU_EPAYMENT_IRN_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Enabled')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('Disabled')
                )
            ),
            'PAYU_PAYMENT_STATES_OPTIONS' => $this->getStatesList(),
            'PAYU_PAYMENT_STATUS_PENDING' => Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            'PAYU_PAYMENT_STATUS_SENT' => Configuration::get('PAYU_PAYMENT_STATUS_SENT'),
            'PAYU_PAYMENT_STATUS_COMPLETED' => Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'),
            'PAYU_PAYMENT_STATUS_CANCELED' => Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'),
            'PAYU_PAYMENT_STATUS_REJECTED' => Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'),
        ));

        return $this->hookBackOfficeHeader() . $this->fetchTemplate('/views/templates/admin/office.tpl');
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function fetchTemplate($name)
    {
        if (version_compare(_PS_VERSION_, '1.4', 'lt'))
            $this->context->smarty->currentTemplate = $name;
        elseif (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            $views = 'views/templates/';
            if (file_exists(dirname(__FILE__) . '/' . $name))
                return $this->display(__FILE__, $name);
            elseif (file_exists(dirname(__FILE__) . '/' . $views . 'hook/' . $name))
                return $this->display(__FILE__, $views . 'hook/' . $name);
            elseif (file_exists(dirname(__FILE__) . '/' . $views . 'front/' . $name))
                return $this->display(__FILE__, $views . 'front/' . $name);
            elseif (file_exists(dirname(__FILE__) . '/' . $views . 'admin/' . $name))
                return $this->display(__FILE__, $views . 'admin/' . $name);
        }

        return $this->display(__FILE__, $name);
    }

    /**
     * @return string
     */
    public function hookBackOfficeHeader()
    {

        $output = '<script type="text/javascript">var business_platforms = ' . Tools::jsonEncode($this->getBusinessPartnersPayU()) . ';</script>';
        $output .= '<link type="text/css" rel="stylesheet" href="' . _MODULE_DIR_ . $this->name . '/css/payu.css" />';

        $vieworder = Tools::getValue('vieworder');
        $id_order = Tools::getValue('id_order');

        //refund Order V2
        if (false !== $vieworder && false !== $id_order && $this->getBusinessPartnerSetting('type') === self::BUSINESS_PARTNER_TYPE_PLATNOSCI) {
            $order = new Order($id_order);
            $order_payment = $this->getOrderPaymentByOrderId($id_order);

            if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                $order_state = OrderHistory::getLastOrderState($id_order);
                $order_state_id = $order_state->id;
            } else
                $order_state_id = $order->current_state;

            if ($order->module = 'payu') {
                switch ($order_state_id) {
                    case Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'):
                        $refundable = true;
                        $deliverable = false;
                        break;
                    case Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'):
                        $refundable = true;
                        $deliverable = false;
                        break;
                    default:
                        $refundable = false;
                        $deliverable = false;
                }
            } else {
                $refundable = false;
                $deliverable = false;
            }
        } //refund ePayment
        else if (false !== $vieworder && false !== $id_order && $this->getBusinessPartnerSetting('type') === self::BUSINESS_PARTNER_TYPE_EPAYMENT) {
            $order = new Order($id_order);

            if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                $order_state = OrderHistory::getLastOrderState($id_order);
                $order_state_id = $order_state->id;
            } else
                $order_state_id = $order->current_state;

            if ($order->module = 'payu') {

                switch ($order_state_id) {
                    case Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'):
                        $refundable = true;
                        $deliverable = false;
                        break;
                    case Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'):
                        $refundable = true;
                        $deliverable = true;
                        break;
                    default:
                        $refundable = false;
                        $deliverable = false;
                }

            } else {
                $refundable = false;
                $deliverable = false;
            }

            $refundable = $refundable && Configuration::get('PAYU_EPAYMENT_IRN');
            $deliverable = $deliverable && Configuration::get('PAYU_EPAYMENT_IDN');

        } else {
            $refundable = false;
            $deliverable = false;
        }

        $refund_type = Tools::getValue('payu_refund_type');
        $refund_amount = $refund_type === 'full' ? $order->total_paid : (float)Tools::getValue('payu_refund_amount');

        $this->context->smarty->assign('payu_refund_amount', $refund_amount);
        if (isset($order) && is_object($order))
            $this->context->smarty->assign('payu_refund_full_amount', $order->total_paid);
        $this->context->smarty->assign('payu_refund_type', $refund_type);
        $this->context->smarty->assign('show_refund', $refundable);
        $this->context->smarty->assign('show_delivery', $deliverable);

        $refund_errors = array();

        if ($refundable && empty($refund_errors) && Tools::getValue('submitPayuRefund')) { //  refund form is submitted

            if ($refund_amount > $order->total_paid)
                $refund_errors[] = $this->l('The refund amount you entered is greater than paid amount.');

            $payu_trans = $this->getPayuTransaction($id_order);

            $ref_no = 0;
            if (version_compare(_PS_VERSION_, '1.5', 'lt'))
                $ref_no = $payu_trans['id_payu_transaction'];
            else {
                foreach ($order->getOrderPaymentCollection() as $payment)
                    $ref_no = $payment->transaction_id;
            }

            if (empty($refund_errors)) {
                $currency = Currency::getCurrency($order->id_currency);

                if ($currency['iso_code'] != $payu_trans['currency'] && $payu_trans['payu_amount'] > 0) {
                    $refund_amount *= $payu_trans['payu_amount'] / $payu_trans['amount'];
                    $refund_curreny = $payu_trans['payu_currency'];
                } else
                    $refund_curreny = $currency['iso_code'];

                if ($this->getBusinessPartnerSetting('type') === self::BUSINESS_PARTNER_TYPE_PLATNOSCI) {

                    $refund = $this->payuOrderRefund($refund_amount, $order_payment['id_session']);

                    if (!empty($refund)) {
                        if (!($refund[0] === true))
                            $refund_errors[] = $this->l('Refund error: ' . $refund[1]);
                    } else
                        $refund_errors[] = $this->l('Refund error...');

                    if (empty($refund_errors)) {   //  change order status
                        // Create new OrderHistory
                        $history = new OrderHistory();
                        $history->id_order = (int)$id_order;
                        $history->id_employee = (int)$this->context->employee->id;

                        $use_existings_payment = false;
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'), $id_order, $use_existings_payment);
                        $history->addWithemail(true, array());

                        if (version_compare(_PS_VERSION_, '1.5', 'lt'))
                            Tools::redirectAdmin('index.php?tab=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                        else
                            Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                    }

                } else {

                    $irn = new PayuIRN(Configuration::get('PAYU_EPAYMENT_MERCHANT'), Configuration::get('PAYU_EPAYMENT_SECRET_KEY'));
                    $irn->setQueryUrl($this->getBusinessPartnerSetting('irn_url'));
                    $irn->setPayuReference($ref_no);
                    $irn->setOrderAmount($payu_trans['payu_amount']);
                    $irn->setRefundAmount($refund_amount);
                    $irn->setOrderCurrency($refund_curreny);

                    $irn_response = $irn->processRequest();

                    if (!isset($irn_response['RESPONSE_CODE']) || 1 != $irn_response['RESPONSE_CODE']) {
                        $error = isset($irn_response['RESPONSE_MSG']) ? $irn_response['RESPONSE_MSG'] :
                            (is_string($irn_response['RESPONSE']) ? strip_tags($irn_response['RESPONSE']) : 'unknown');
                        $refund_errors[] = $this->l('Refund error: ') . $error;
                    }

                    if (empty($refund_errors)) {   //  change order status
                        // Create new OrderHistory
                        $history = new OrderHistory();
                        $history->id_order = (int)$id_order;
                        $history->id_employee = (int)$this->context->employee->id;

                        $use_existings_payment = false;
                        /*if (!$order->hasInvoice())
                            $use_existings_payment = true;*/
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'), $id_order, $use_existings_payment);
                        $history->addWithemail(true, array());

                        if (version_compare(_PS_VERSION_, '1.5', 'lt'))
                            Tools::redirectAdmin('index.php?tab=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                        else
                            Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                    }

                }
            }
        }

        $delivery_errors = array();

        if ($deliverable && empty($delivery_errors) && Tools::getValue('submitPayuDelivery')) {    //	delivery confirmation form is submitted

            $payu_trans = $this->getPayuTransaction($id_order);

            $ref_no = 0;
            if (version_compare(_PS_VERSION_, '1.5', 'lt'))
                $ref_no = $payu_trans['id_payu_transaction'];
            else {
                foreach ($order->getOrderPaymentCollection() as $payment)
                    $ref_no = $payment->transaction_id;
            }

            $idn = new PayuIDN(Configuration::get('PAYU_EPAYMENT_MERCHANT'), Configuration::get('PAYU_EPAYMENT_SECRET_KEY'));
            $idn->setQueryUrl($this->getBusinessPartnerSetting('idn_url'));
            $idn->setPayuReference($ref_no);
            $idn->setOrderAmount($payu_trans['payu_amount']);
            $idn->setChargeAmount($payu_trans['payu_amount']);
            $idn->setOrderCurrency($payu_trans['payu_currency']);
            $idn_response = $idn->processRequest();

            if (!isset($idn_response['RESPONSE_CODE']) || 1 != $idn_response['RESPONSE_CODE']) {
                $error = isset($idn_response['RESPONSE_MSG']) ? $idn_response['RESPONSE_MSG'] : (is_string($idn_response) ? strip_tags($idn_response) : 'unknown');
                $delivery_errors[] = $this->l('PayU error message on IDN request: ') . $error;
            }

            if (empty($delivery_errors)) {
                //  change order status
                // Create new OrderHistory
                $history = new OrderHistory();
                $history->id_order = (int)$id_order;
                $history->id_employee = (int)$this->context->employee->id;

                $use_existings_payment = false;
                $history->addWithemail(true, array());

                if (version_compare(_PS_VERSION_, '1.5', 'lt'))
                    Tools::redirectAdmin('index.php?tab=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                else
                    Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
            }
        }

        $this->context->smarty->assign('payu_delivery_errors', $delivery_errors);

        $this->context->smarty->assign('payu_refund_errors', $refund_errors);

        if (version_compare(_PS_VERSION_, '1.6', 'lt'))
            $template = $output . $this->fetchTemplate('/views/templates/admin/header.tpl');
        else
            $template = $output . $this->fetchTemplate('/views/templates/admin/header16.tpl');

        return $template;
    }

    public function hookHeader()
    {
        if (version_compare(_PS_VERSION_, '1.6', 'lt')) {
            Tools::addCSS(($this->_path) . 'css/payu.css', 'all');
        } else {
            $this->context->controller->addCSS(($this->_path) . 'css/payu.css', 'all');
        }
    }

    public function payuOrderRefund($value, $ref_no)
    {
        try {

            $refund = OpenPayU_Refund::create(
                $ref_no,
                'PayU Refund',
                round($value * 100)
            );

            if ($refund->getStatus() == 'SUCCESS')
                return array(true);
            else {
                Logger::addLog($this->displayName . ' Order Refund error: ', 1);
                return array(false, 'Status code: ' . $refund->getStatus());
            }

        } catch (OpenPayU_Exception $e) {
            Logger::addLog($this->displayName . ' Order Refund error: ' . $e->getMessage(), 1);
            return array(false, $e->getMessage());
        }

        return false;

    }

    /**
     * Return PayU business partners
     *
     * @return array
     */
    private function getBusinessPartnersPayU()
    {
        $business_partners = array(
            'payu_pl' => array(
                'name' => 'PayU Poland - PayU',
                'type' => self::BUSINESS_PARTNER_TYPE_PLATNOSCI,
            ),
            'payu_ro_epayment' => array(
                'name' => 'PayU Romania - ePayment',
                'type' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
                'lu_url' => 'https://secure.epayment.ro/order/lu.php',
                'idn_url' => 'https://secure.epayment.ro/order/idn.php',
                'irn_url' => 'https://secure.epayment.ro/order/irn.php'
            ),
            'payu_ru_epayment' => array(
                'name' => 'PayU Russia - ePayment',
                'type' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
                'lu_url' => 'https://secure.payu.ru/order/lu.php',
                'idn_url' => 'https://secure.payu.ru/order/idn.php',
                'irn_url' => 'https://secure.payu.ru/order/irn.php'
            ),
            'payu_ua_epayment' => array(
                'name' => 'PayU Ukraine - ePayment',
                'type' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
                'lu_url' => 'https://secure.payu.ua/order/lu.php',
                'idn_url' => 'https://secure.payu.ua/order/idn.php',
                'irn_url' => 'https://secure.payu.ua/order/irn.php'
            ),
            'payu_tr_epayment' => array(
                'name' => 'PayU Turkey - ePayment',
                'type' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
                'lu_url' => 'https://secure.payu.com.tr/order/lu.php',
                'idn_url' => 'https://secure.payu.com.tr/order/idn.php',
                'irn_url' => 'https://secure.payu.com.tr/order/irn.php'
            ),
            'payu_hu_epayment' => array(
                'name' => 'PayU Hungary - ePayment',
                'type' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
                'lu_url' => 'https://secure.payu.hu/order/lu.php',
                'idn_url' => 'https://secure.payu.hu/order/idn.php',
                'irn_url' => 'https://secure.payu.hu/order/irn.php'
            ),
        );

        return $business_partners;
    }


    /**
     * @param string $setting_name
     * @param null|string $business_partner
     * @return null|string
     */
    public function getBusinessPartnerSetting($setting_name, $business_partner = null)
    {
        $business_partner = $business_partner === null ? Configuration::get('PAYU_PAYMENT_PLATFORM') : $business_partner;
        $settings = $this->getBusinessPartnersPayU();
        return isset($settings[$business_partner][$setting_name]) ? $settings[$business_partner][$setting_name] : null;
    }

    /**
     * @return array
     */
    private function getBusinessPartnersList()
    {
        $list = array();
        $business_partners_area_list = $this->getBusinessPartnersPayU();

        if (empty($business_partners_area_list))
            return array();

        foreach ($business_partners_area_list as $id_area_partner => $partner)
            $list[] = array('id' => $id_area_partner, 'name' => $partner['name'], 'type' => $partner['type']);

        return $list;
    }


    /**
     * @return mixed
     */
    public function hookPayment()
    {
        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            $link = $this->getModuleAddress() . 'backward_compatibility/payment.php';
        } else {
            $link = $this->context->link->getModuleLink('payu', 'payment');
        }

        $this->context->smarty->assign(array('image' => $this->getPayButtonUrl(), 'actionUrl' => $link));

        if (version_compare(_PS_VERSION_, '1.6', 'lt')) {
            $template = $this->fetchTemplate('/views/templates/hook/payment.tpl');
        } else {
            $template = $this->fetchTemplate('/views/templates/hook/payment16.tpl');
        }

        return $template;
    }

    public function hookDisplayPaymentEU()
    {
        $payment_options = array(
            'cta_text' => $this->l('Pay with PayU'),
            'logo' => $this->getPayButtonUrl(),
            'action' => $this->context->link->getModuleLink('payu', 'payment')
        );

        return $payment_options;
    }

    /**
     * @return null|string
     */
    public function hookAdminOrder()
    {
        $output = '';
        $this->id_order = Tools::getValue('id_order');

        $order_payment = $this->getOrderPaymentByOrderId($this->id_order);

        if (!(int)Configuration::get('PAYU_SELF_RETURN') &&
            !($this->getBusinessPartnerSetting('type') == self::BUSINESS_PARTNER_TYPE_PLATNOSCI) &&
            !($order_payment['status'] == self::PAYMENT_STATUS_END)
        )
            return null;

        $this->payu_order_id = $order_payment['id_session'];

        if (Tools::isSubmit('submitpayustatus') && $this->payu_order_id && $order_payment['status'] == self::ORDER_V2_WAITING_FOR_CONFIRMATION) {
            if (trim(Tools::getValue('PAYU_PAYMENT_STATUS')) &&
                $this->sendPaymentUpdate(trim(Tools::getValue('PAYU_PAYMENT_STATUS')))
            )
                $output .= $this->displayConfirmation($this->l('Update status request has been sent'));
            else
                $output .= $this->displayError($this->l('Update status request has not been completed correctly.'));
        }

        $this->context->smarty->assign(array(
            'PAYU_PAYMENT_STATUS_OPTIONS' => $this->getPaymentAcceptanceStatusesList(),
            'PAYU_PAYMENT_STATUS' => $order_payment['status'],
            'PAYU_PAYMENT_ACCEPT' => $order_payment['status'] == self::ORDER_V2_WAITING_FOR_CONFIRMATION
        ));


        return $output . $this->fetchTemplate('/views/templates/admin/status.tpl');
    }

    /**
     * @param $status
     * @return bool
     */
    private function sendPaymentUpdate($status)
    {
        if (!empty($status) && !empty($this->payu_order_id)) {
            if ($status == self::ORDER_STATUS_CANCEL)
                $result = OpenPayU_Order::cancel($this->payu_order_id);

            elseif ($status == self::ORDER_STATUS_COMPLETE) {
                $status_update = array(
                    "orderId" => $this->payu_order_id,
                    "orderStatus" => self::ORDER_V2_COMPLETED
                );
                $result = OpenPayU_Order::statusUpdate($status_update);
            }
            if ($result->getStatus() == 'SUCCESS') {
                $this->updateOrderData();
                return true;
            } else {
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage() . ' ' . $this->payu_order_id), 1);
                return false;
            }
        }
        return false;
    }

    /**
     * Hook display on payment return
     *
     * @return string Content
     */
    public function paymentReturn()
    {
        $errorval = (int)Tools::getValue('error', 0);

        if ($errorval != 0)
            $this->context->smarty->assign(array('errormessage' => ''));

        return $this->fetchTemplate('/views/templates/front/', 'payment_return');
    }

    /**
     * Convert to amount
     *
     * @param $value
     * @return int
     */
    private function toAmount($value)
    {
        $val = $value * 100;
        $round = (int)round($val);
        return $round;
    }

    /**
     * @return array
     */
    private function getValidityTimeList()
    {
        return array(
            array(
                'id' => '1440',
                'name' => '1440 min (24h)'
            ),
            array(
                'id' => '720',
                'name' => '720 min (12h)'
            ),
            array(
                'id' => '360',
                'name' => '360 min (6h)'
            ),
            array(
                'id' => '60',
                'name' => '60 min (1h)'
            ),
            array(
                'id' => '30',
                'name' => '30 min'
            )
        );
    }

    /**
     * @return array
     */
    private function getPaymentAcceptanceStatusesList()
    {
        return array(
            array('id' => self::ORDER_STATUS_COMPLETE, 'name' => $this->l('Payment accepted')),
            array('id' => self::ORDER_STATUS_CANCEL, 'name' => $this->l('Payment rejected'))
        );
    }

    /**
     * @return array|null
     */
    public function getStatesList()
    {
        $states = OrderState::getOrderStates($this->context->language->id);
        $list = array();

        if (empty($states))
            return null;

        foreach ($states as $state) $list[] = array('id' => $state['id_order_state'], 'name' => $state['name']);

        return $list;
    }

    /**
     * @param bool $http
     * @param bool $entities
     * @return string
     */
    public function getModuleAddress($http = true, $entities = true)
    {
        return $this->getShopDomainAddress($http, $entities) . (__PS_BASE_URI__ . 'modules/' . $this->name . '/');
    }

    /**
     * @param bool $http
     * @param bool $entities
     * @return string
     */
    public static function getShopDomainAddress($http = false, $entities = false)
    {
        if (method_exists('Tools', 'getShopDomainSsl'))
            return Tools::getShopDomainSsl($http, $entities);
        else {
            if (!($domain = Configuration::get('PS_SHOP_DOMAIN_SSL')))
                $domain = Tools::getHttpHost();

            if ($entities)
                $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');

            if ($http)
                $domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $domain;

            return $domain;
        }
    }

    /**
     * @return array
     */
    public function orderCreateRequest()
    {

        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        $currency = Currency::getCurrency($this->cart->id_currency);
        $return_array = array();

        $items = array();

        $cart_products = $this->cart->getProducts();

        //discounts and cart rules
        list($items, $total) = $this->getDiscountsAndCartRules($items, $cart_products);
        // Wrapping fees
        list($wrapping_fees_tax_inc, $items, $total) = $this->getWrappingFees($items, $total);

        $carrier = $this->getCarrier($this->cart);
        $grand_total = $this->getGrandTotal($wrapping_fees_tax_inc, $total);
        list($order_complete_link, $order_notify_link, $order_cancel_link) = $this->getLinks();
        if (!empty($this->cart->id_customer)) {
            $customer = new Customer((int)$this->cart->id_customer);

            if ($customer->email) {
                $customer_sheet = $this->getCustomer($customer);

            }
        }
        //prepare data for OrderCreateRequest
        $ocreq = $this->prepareOrder($items, $customer_sheet, $order_notify_link, $order_cancel_link, $order_complete_link, $currency, $grand_total, $carrier);
        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id, 'OrderCreateRequest: ');
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderCreateResponse: ');
            if ($result->getStatus() == 'SUCCESS') {
                $context = Context::getContext();
                $context->cookie->__set('payu_order_id', $result->getResponse()->orderId);

                $return_array = array(
                    'redirectUri' => urldecode($result->getResponse()->redirectUri.'&lang='.Language::getIsoById($this->context->language->id)),
                    'orderId' => $result->getResponse()->orderId
                );
            } else {
                SimplePayuLogger::addLog('order', __FUNCTION__, 'OpenPayU_Order::create($ocreq) NOT success!! ' . $this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage(), $this->payu_order_id));
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage()), 1);
            }

        } catch (Exception $e) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Exception catched! ' . $this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()));
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()), 1);
        }
        return $return_array;
    }

    public function addProductsToOrder($cartProducts, &$total)
    {
        foreach ($cartProducts as $product) {

            $price_wt = $this->toAmount($product['price_wt']);
            $total += $this->toAmount($product['total_wt']);
            $items[] = array(
                'quantity' => (int)$product['quantity'],
                'name' => $product['name'],
                'unitPrice' => $price_wt
            );
        }
        return $items;

    }

    /**
     * @param CartCore $cart
     * @return string
     */
    public function getLuForm(CartCore $cart)
    {
        $merchant_id = Configuration::get('PAYU_EPAYMENT_MERCHANT');
        $secret_key = Configuration::get('PAYU_EPAYMENT_SECRET_KEY');
        $url = $this->getBusinessPartnerSetting('lu_url');

        if (empty($merchant_id) || empty($secret_key) || empty($url))
            return false;

        $live_update = new PayuLu($merchant_id, $secret_key);
        $live_update->setQueryUrl($url);

        $this->validateOrder($cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            $cart->getOrderTotal(true, Cart::BOTH), $this->displayName, null,
            null, (int)$cart->id_currency, false, $cart->secure_key,
            Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
        );

        $this->current_order = $this->{'currentOrder'};

        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            $this->current_order_reference = '';
            $internal_reference = '#' . str_pad($this->current_order, 6, '0', STR_PAD_LEFT);
            $order_ref = $this->current_order . '|' . str_pad($this->current_order, 6, '0', STR_PAD_LEFT);
            $order_id = $this->current_order;
            $backref_url = $this->getModuleAddress() . 'backward_compatibility/return.php?order_ref=' . $this->current_order;
        } else {
            $this->current_order_reference = $this->{'currentOrderReference'};
            $internal_reference = $this->{'currentOrderReference'};
            $order_ref = $this->{'currentOrder'} . '|' . $this->{'currentOrderReference'};
            $order_id = $this->{'currentOrder'};
            $backref_url = Context::getContext()->link->getModuleLink('payu', 'return', array('order_ref' => $this->current_order));
        }

        $live_update->setBackRef($backref_url);

        $live_update->setOrderRef($order_ref);

        $currency = Currency::getCurrency($cart->id_currency);
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $lang_iso_code = Language::getIsoById($default_lang);
        $live_update->setPaymentCurrency($currency['iso_code']);
        $live_update->setLanguage(Tools::strtoupper($lang_iso_code));

        $payu_product = new PayuProduct();
        $payu_product->setName('Payment for order ' . $internal_reference);
        $payu_product->setCode($internal_reference);
        $payu_product->setPrice($cart->getOrderTotal(true, Cart::BOTH));
        $payu_product->setTax(0);
        $payu_product->setQuantity(1);

        $live_update->addProduct($payu_product);

        if (!empty($cart->id_customer)) {
            $customer = new Customer((int)$cart->id_customer);
            if ($customer->email) {
                if (!empty($cart->id_address_invoice) && Configuration::get('PS_INVOICE')) {
                    $address = new Address((int)$cart->id_address_invoice);
                    $country = new Country((int)$address->id_country);

                    $billing = new PayuAddress();
                    $billing->setFirstName($address->firstname);
                    $billing->setLastName($address->lastname);
                    $billing->setEmail($customer->email);
                    $billing->setPhone(!$address->phone ? $address->phone_mobile : $address->phone);
                    $billing->setAddress($address->address1);
                    $billing->setAddress2($address->address2);
                    $billing->setZipCode($address->postcode);
                    $billing->setCity($address->city);
                    $billing->setCountryCode(Tools::strtoupper($country->iso_code));

                    $live_update->setBillingAddress($billing);
                }

                if (!empty($cart->id_address_delivery)) {
                    $address = new Address((int)$cart->id_address_delivery);
                    $country = new Country((int)$address->id_country);

                    $delivery = new PayuAddress();
                    $delivery->setFirstName($address->firstname);
                    $delivery->setLastName($address->lastname);
                    $delivery->setEmail($customer->email);
                    $delivery->setPhone(!$address->phone ? $address->phone_mobile : $address->phone);
                    $delivery->setAddress($address->address1);
                    $delivery->setAddress2($address->address2);
                    $delivery->setZipCode($address->postcode);
                    $delivery->setCity($address->city);
                    $delivery->setCountryCode(Tools::strtoupper($country->iso_code));
                    $live_update->setDeliveryAddress($delivery);
                }
            }
        }

        $lu_form = $live_update->renderPaymentForm(null);

        $this->savePayuTransaction($order_id, $cart->getOrderTotal(true, Cart::BOTH), Currency::getCurrency($cart->id_currency));

        return $lu_form;
    }

    /**
     * @return array|null
     */
    public function getCarrier()
    {
        $carrier_list = null;

        $country_code = Tools::strtoupper(Configuration::get('PS_LOCALE_COUNTRY'));
        $country = new Country(Country::getByIso($country_code));
        $cart_products = $this->cart->getProducts();
        $free_shipping = false;

        // turned off for 1.4
        if (version_compare(_PS_VERSION_, '1.5', 'gt')) {
            foreach ($this->cart->getCartRules() as $rule) {
                if ($rule['free_shipping']) {
                    $free_shipping = true;
                    break;
                }
            }
        }

        if ($this->cart->id_carrier > 0) {
            $selected_carrier = new Carrier($this->cart->id_carrier);
            $shipping_method = $selected_carrier->getShippingMethod();

            if ($free_shipping == false) {
                if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                    $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                        ? 0 : $this->cart->getOrderShippingCost((int)$this->cart->id_carrier, true, $country, $cart_products));
                } else {
                    $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                        ? 0 : $this->cart->getPackageShippingCost((int)$this->cart->id_carrier, true, $country, $cart_products));
                }

                if ((int)$selected_carrier->active == 1) {

                    $carrier_list = array(
                        'name' => $selected_carrier->name . ' (' . $selected_carrier->id . ')',
                        'quantity' => 1,
                        'unitPrice' => $this->toAmount($price)
                    );

                }
            }
        }

        return $carrier_list;
    }

    /**
     * @param $id_session
     * @return bool|int
     */
    public function getOrderIdBySessionId($id_session)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'DB query: SELECT `id_order` FROM `' . _DB_PREFIX_ . 'order_payu_payments` WHERE `id_session`="' . addslashes($id_session) . '"', $this->payu_order_id);
        $result = Db::getInstance()->getRow('
			SELECT `id_order` FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"');
        SimplePayuLogger::addLog('notification', __FUNCTION__,  print_r($result, true), $this->payu_order_id, 'DB query result ');
        if ($result)
            return (int)$result['id_order'];
        else
            return false;
    }

    /**
     * @param $id_order
     * @return bool
     */
    public function getOrderPaymentByOrderId($id_order)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'DB query: SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments` WHERE `id_order`="' . addslashes($id_order) . '"', $this->payu_order_id);
        $result = Db::getInstance()->getRow('
			SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_order`="' . addslashes($id_order) . '"');
        if ($result)
            return $result;

        return false;
    }

    /**
     * @param $id_session
     * @return bool
     */
    public function getOrderPaymentBySessionId($id_session)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'DB query: SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments WHERE `id_session`="' . addslashes($id_session) . '"', $this->payu_order_id);
        $result = Db::getInstance()->getRow('
			SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"');

        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'DB query result ');
        if ($result)
            return $result;

        return false;
    }

    /**
     * @param $id_session
     * @return bool
     */
    public function updateOrderPaymentSessionId($id_session, $id_payu_order)
    {
        return Db::getInstance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'order_payu_payments`
			SET `id_session` = "' . $id_payu_order . '"
			WHERE `id_session`="' . $id_session . '"');
    }

    /**
     * @param $status
     * @return bool
     */
    public function updateOrderPaymentStatusBySessionId($status)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, '
			UPDATE `' . _DB_PREFIX_ . 'order_payu_payments` SET id_order = "' . (int)$this->id_order . '", status = "' . addslashes($status) . '", update_at = NOW()
			WHERE `id_session`="' . addslashes($this->payu_order_id) . '"', $this->payu_order_id);
        return Db::getInstance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'order_payu_payments`
			SET id_order = "' . (int)$this->id_order . '", status = "' . addslashes($status) . '", update_at = NOW()
			WHERE `id_session`="' . addslashes($this->payu_order_id) . '"');
    }

    public function checkIfStatusCompleted($id_session)
    {
        $result = Db::getInstance()->getRow('
			SELECT status FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"');
        if ($result['status'] == PayU::ORDER_V2_COMPLETED)
            return true;
        return false;
    }

    /**
     * @param string $status
     * @return mixed
     */
    public function addOrderSessionId($status = '')
    {
        SimplePayuLogger::addLog('order', __FUNCTION__, 'DB Insert
			INSERT INTO `' . _DB_PREFIX_ . 'order_payu_payments` (`id_order`, `id_cart`, `id_session`,  `status`,  `create_at`)
				VALUES ("' . (int)$this->currentOrder . '", "' . (int)$this->id_cart . '",  "' . $this->payu_order_id . '",   "' . addslashes($status) . '", NOW())', $this->payu_order_id);
        if (Db::getInstance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'order_payu_payments` (`id_order`, `id_cart`, `id_session`,  `status`,  `create_at`)
				VALUES ("' . (int)$this->currentOrder . '", "' . (int)$this->id_cart . '",  "' . $this->payu_order_id . '",   "' . addslashes($status) . '", NOW())')
        )
            return (int)Db::getInstance()->Insert_ID();

        return false;
    }

    /**
     * @param $status
     * @return bool
     */
    private function updateOrderState($status)
    {
        if ($status === self::ORDER_V2_COMPLETED) {
            if (!$this->status_completed) {
                SimplePayuLogger::addLog('notification', __FUNCTION__, 'I havent been here yet, i will mark my territory! No other thread should go further! ', $this->payu_order_id);
                $this->status_completed = true;
            } else {
                SimplePayuLogger::addLog('notification', __FUNCTION__, 'Status is already completed, Im getting out of here! ', $this->payu_order_id);
            }
        }

        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Entrance: ', $this->payu_order_id);

        if (!empty($this->order->id) && !empty($status)) {
            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Payu order status: ' . $status, $this->payu_order_id);
            $order_state_id = $this->getCurrentPrestaOrderState();
            if ($this->checkIfStatusCompleted($this->payu_order_id)) {
                return true;
            }

            switch ($status) {
                case self::ORDER_V2_COMPLETED :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->order->id);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Addition to Prestashop order status history: ' . Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->payu_order_id);
                        $history->addWithemail(true);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Email sent', $this->payu_order_id);
                    }
                    break;
                case self::ORDER_V2_CANCELED :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    break;
                case self::ORDER_V2_WAITING_FOR_CONFIRMATION :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_SENT')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_SENT'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    break;
                case self::ORDER_V2_REJECTED :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_REJECTED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    break;
                case self::ORDER_V2_PENDING :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_PENDING'), $this->order->id);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Addition to Prestashop order status history: ' . Configuration::get('PAYU_PAYMENT_STATUS_PENDING'), $this->payu_order_id);

                        $history->addWithemail(false);
                    }
                    break;
            }

            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Check if status is completed in DB: ', $this->payu_order_id);
            if (!$this->checkIfStatusCompleted($this->payu_order_id)) {
                SimplePayuLogger::addLog('notification', __FUNCTION__, 'Status in DB is not COMPLETED, going to change status id DB ', $this->payu_order_id);
                return $this->updateOrderPaymentStatusBySessionId($status);
            }
        }

        return false;
    }

    public function updateOrderData($response_notification = null)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        if (empty($this->payu_order_id)) {
            Logger::addLog($this->displayName . ' ' . 'Can not get order information - id_session is empty', 1);
        }

        $result=null;

        if ($response_notification) {
            $response = $response_notification;
        } else {
            $raw = OpenPayU_Order::retrieve($this->payu_order_id);
            $response = $raw->getResponse();

        }
        SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderRetrieve response object: ');

        $payu_order = isset($response_notification) ? $response->order : $response->orders[0];

        if (isset($payu_order)) {

            if (!empty($this->id_order)) {
                $this->order = new Order($this->id_order);

                SimplePayuLogger::addLog('notification', __FUNCTION__, 'Order exists in PayU system ', $this->payu_order_id);
                if ($this->getCurrentPrestaOrderState() != 2) {
                    SimplePayuLogger::addLog('notification', __FUNCTION__, 'Prestashop order status IS NOT COMPLETED, go to status actualization', $this->payu_order_id);
                    if ($this->order->update()) {
                        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Prestashop updated order status, go to PayU status update to: ' . $payu_order->status, $this->payu_order_id);
                        $this->updateOrderState(isset($payu_order->status) ? $payu_order->status : null);
                    }
                }
            }
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    public function interpretReturnParameters($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);

        if (!isset($parameters['order_ref']) || !is_numeric($parameters['order_ref']))
            return true;

        $order_id = (int)$parameters['order_ref'];

        $history = new OrderHistory();
        $history->id_order = $order_id;

        $error = Tools::getValue('err');

        if ($error) {
            $history->changeIdOrderState((int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'), $order_id);
            $history->addWithemail(true);
        }

        // validate signature
        if (true !== PayuSignature::validateSignedUrl($url, Configuration::get('PAYU_EPAYMENT_SECRET_KEY')))
            return false;

        // check if IPN is disabled
        if (!Configuration::get('PAYU_EPAYMENT_IPN')) {
            if (Tools::getIsset(Tools::getValue('TRS')) && Tools::getValue('TRS') === 'AUTH') {
                // mark order as complete
                $history->changeIdOrderState((int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $order_id);
                $history->addWithemail(true);
            }
        }

        return true;
    }

    /**
     * Interpret instant payment notification
     *
     * @param array $params
     * @return array|bool
     */
    public function interpretIPN(Array $params)
    {
        if (!isset($params['REFNOEXT'], $params['HASH'], $params['ORDERSTATUS'], $params['REFNO'], $params['IPN_TOTALGENERAL'], $params['CURRENCY'],
            $params['HASH'], $params['IPN_PID'], $params['IPN_PNAME'], $params['IPN_DATE'])
        )
            return array('error' => 'One or more parameters are missing');

        $order_id = (int)$params['REFNOEXT'];

        if (empty($order_id))
            return array('error' => 'Missing REFNOEXT');

        if ($this->getBusinessPartnerSetting('type') !== self::BUSINESS_PARTNER_TYPE_EPAYMENT)
            return array('error' => 'Incorrect business partner');

        if (!Configuration::get('PAYU_EPAYMENT_IPN'))
            return array('error' => 'IPN disabled');

        if ($params['HASH'] != PayuSignature::generateHmac(
                Configuration::get('PAYU_EPAYMENT_SECRET_KEY'), PayuSignature::signatureString($params, array('HASH')))
        )
            return array('error' => 'Invalid signature');

        try {
            $history = new OrderHistory();
            $history->id_order = $order_id;

            switch ($params['ORDERSTATUS']) {
                case 'PAYMENT_AUTHORIZED':
                case 'PAYMENT_RECEIVED':
                    $new_status = (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED');

                    $history->changeIdOrderState($new_status, $order_id);
                    $history->addWithemail(true);

                    $order = new Order($order_id);

                    if (version_compare(_PS_VERSION_, '1.5', 'ge')) {
                        $payment = $order->getOrderPaymentCollection();
                        $payments = $payment->getAll();
                        $payments[$payment->count() - 1]->transaction_id = $params['REFNO'];
                        $payments[$payment->count() - 1]->update();
                    }

                    $this->updatePayuTransaction($order_id, (int)$params['REFNO'], $params['IPN_TOTALGENERAL'], $params['CURRENCY']);
                    break;
            }

            $date = date('YmdGis');

            $response_params = array(
                $params['IPN_PID'][0],
                $params['IPN_PNAME'][0],
                $params['IPN_DATE'],
                $date
            );

            $hash = PayuSignature::generateHmac(
                Configuration::get('PAYU_EPAYMENT_SECRET_KEY'), PayuSignature::signatureString($response_params, array('HASH')));

            return array(
                'date' => $date,
                'hash' => $hash,
            );
        } catch (Exception $e) {
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage() . ' id_order: ' . $order_id), 1);
            return false;
        }
    }

    public function updatePayuTransaction($order_id, $transaction_id, $payu_amount, $payu_currency)
    {
        return Db::getInstance()->Execute('
			UPDATE `' . _DB_PREFIX_ . 'payu_payments`
				SET
					id_payu_transaction = ' . (int)$transaction_id . ',
					payu_amount = ' . (float)$payu_amount . ',
					payu_currency = "' . addslashes($payu_currency) . '",
					update_at = NOW()
				WHERE id_order = ' . (int)$order_id . '
		');
    }

    public function savePayuTransaction($order_id, $amount, $currency)
    {
        return Db::getInstance()->Execute('
			INSERT INTO
				`' . _DB_PREFIX_ . 'payu_payments`
				SET
					id_order = ' . (int)$order_id . ',
					amount = ' . (float)$amount . ',
					currency = "' . addslashes($currency['iso_code']) . '",
					create_at = NOW()
		');
    }

    public function getPayuTransaction($order_id)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'payu_payments` WHERE `id_order` = ' . (int)$order_id . ' ORDER BY `update_at` DESC');
    }

    public function addMsgToOrder($message, $prestaOrderId)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Addition of PAYMENT_ID info', $this->payu_order_id);

        $msg = new Message();
        $message = strip_tags($message, '<br>');
        $msg->message = $message;
        $msg->id_order = intval($prestaOrderId);
        $msg->private = 1;
        $msg->add();

    }

    /**
     * @param $wrapping_fees_tax_inc
     * @param $total
     * @return int
     */
    private function getGrandTotal($wrapping_fees_tax_inc, $total)
    {
        if ($this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH)) + $wrapping_fees_tax_inc < $total) {
            $grand_total = $total;
            return $grand_total;
        } else {
            $grand_total = $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH)) + $wrapping_fees_tax_inc;
            return $grand_total;
        }
    }

    /**
     * @return array
     */
    private function getLinks()
    {
        if (version_compare(_PS_VERSION_, '1.5', 'gt')) {
            $order_complete_link = $this->context->link->getModuleLink('payu', 'success');
            $order_notify_link = $this->context->link->getModuleLink('payu', 'notification');
            $order_cancel_link = $this->context->link->getPageLink('order.php', true);
            return array($order_complete_link, $order_notify_link, $order_cancel_link);
        } else {
            $link = new Link();
            $order_complete_link = $this->getModuleAddress() . 'backward_compatibility/success.php';
            $order_notify_link = $this->getModuleAddress() . 'backward_compatibility/notification.php';
            $order_cancel_link = $link->getPageLink(__PS_BASE_URI__ . 'order.php');
            return array($order_complete_link, $order_notify_link, $order_cancel_link);
        }
    }

    /**
     * @param $customer
     * @return array
     */
    private function getCustomer($customer)
    {
        $customer_sheet = array(
            'email' => $customer->email,
            'firstName' => $customer->firstname,
            'lastName' => $customer->lastname
        );

        if (!empty($this->cart->id_address_delivery)) {
            $address = new Address((int)$this->cart->id_address_delivery);
            $country = new Country((int)$address->id_country);

            if (empty($address->phone))
                $customer_sheet['phone'] = $address->phone_mobile;
            else
                $customer_sheet['phone'] = $address->phone;

            $customer_sheet['delivery'] = array(
                'street' => $address->address1,
                'postalCode' => $address->postcode,
                'city' => $address->city,
                'countryCode' => Tools::strtoupper($country->iso_code),
                'recipientName' => trim($address->firstname . ' ' . $address->lastname),
                'recipientPhone' => $address->phone ? $address->phone : $address->phone_mobile,
                'recipientEmail' => $customer->email
            );

        }

        if (!empty($this->cart->id_address_invoice) && Configuration::get('PS_INVOICE')) {
            $address = new Address((int)$this->cart->id_address_invoice);
            $country = new Country((int)$address->id_country);
            return $customer_sheet;
        }
        return $customer_sheet;
    }

    /**
     * @param $items
     * @param $customer_sheet
     * @param $order_notify_link
     * @param $order_cancel_link
     * @param $order_complete_link
     * @param $currency
     * @param $grand_total
     * @param $carrier
     * @return array
     */
    private function prepareOrder($items, $customer_sheet, $order_notify_link, $order_cancel_link, $order_complete_link, $currency, $grand_total, $carrier)
    {
        $ocreq = array();

        $ocreq['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $ocreq['description'] = $this->l('Order for cart: ') . ' ' . $this->cart->id . ' ' . $this->l(' from the store: ') . ' ' . Configuration::get('PS_SHOP_NAME');
        $ocreq['validityTime'] = 60 * (int)Configuration::get('PAYU_VALIDITY_TIME');
        $ocreq['products'] = $items['products'];
        if ($carrier && is_array($carrier)) {
            array_push($ocreq['products'], $carrier);
        }
        $ocreq['buyer'] = $customer_sheet;
        $ocreq['customerIp'] = $this->getIP();
        $ocreq['notifyUrl'] = $order_notify_link;
        $ocreq['cancelUrl'] = $order_cancel_link;
        $ocreq['continueUrl'] = $order_complete_link . '?id_cart=' . $this->cart->id;
        $ocreq['currencyCode'] = $currency['iso_code'];
        $ocreq['totalAmount'] = $grand_total;
        $ocreq['extOrderId'] = $this->cart->id . '-' . uniqid(true);
        $ocreq['settings']['invoiceDisabled'] = true;

        return $ocreq;
    }

    /**
     * @param $items
     * @param $total
     * @return array
     */
    private function getWrappingFees($items, $total)
    {

        $wrapping_fees_tax_inc = $wrapping_fees = 0;
        if ((int)Configuration::get('PS_GIFT_WRAPPING') && $this->context->cart->gift) {
            $wrapping_fees = $this->toAmount($this->context->cart->getGiftWrappingPrice(false));
            $wrapping_fees_tax_inc = $this->toAmount($this->context->cart->getGiftWrappingPrice());

            $items['products'][] = array(
                'quantity' => 1,
                'name' => $this->l('Gift wrapping'),
                'unitPrice' => $wrapping_fees
            );

            $total += $wrapping_fees_tax_inc;
            return array($wrapping_fees_tax_inc, $items, $total);

        }
        return array($wrapping_fees_tax_inc, $items, $total);
    }

    /**
     * @param $items
     * @param $cart_products
     * @return array
     */
    private function getDiscountsAndCartRules($items, $cart_products)
    {
        $total = '';
        if (version_compare(_PS_VERSION_, '1.5', 'gt')) {
            if ($this->cart->getCartRules()) {
                $items['products'][] = array(
                    'quantity' => 1,
                    'name' => 'Order id ' . $this->cart->id,
                    'unitPrice' => $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH))
                );
                return array($items, $total);
            } else {
                $items['products'] = $this->addProductsToOrder($cart_products, $total);
                return array($items, $total);
            }
        } else {

            if ($this->cart->getDiscounts()) {
                $items['products'][] = array(
                    'quantity' => 1,
                    'name' => 'Order id ' . $this->cart->id,
                    'unitPrice' => $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH))
                );
                return array($items, $total);
            } else {
                $items['products'] = $this->addProductsToOrder($cart_products, $total);
                return array($items, $total);
            }
        }
    }

    /**
     * @return string
     */
    private function getIP()
    {
        return ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return string
     */
    private function getPayButtonUrl()
    {
        $lang = Language::getIsoById($this->context->language->id) == 'pl' ? 'pl' : 'en';
        return str_replace('{lang}', $lang, self::PAY_BUTTON);
    }

    /**
     * @return mixed
     */
    private function getCurrentPrestaOrderState()
    {
        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            $order_state = OrderHistory::getLastOrderState($this->order->id);
            $order_state_id = $order_state->id;
            return $order_state_id;
        } else
            $order_state_id = $this->order->current_state;
        return $order_state_id;
    }

}
