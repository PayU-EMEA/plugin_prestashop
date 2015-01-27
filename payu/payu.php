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

include_once(_PS_MODULE_DIR_ . '/payu/tools/payu/payu_php_lib/openpayu.php');
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
        $this->version = '2.1.6.6';
        $this->author = 'PayU';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4.4', 'max' => '1.6');

        $this->currencies = true;
        $this->currencies_mode = 'radio';

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
            $this->registerHook('leftColumn') &&
            $this->registerHook('rightColumn') &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            (version_compare(_PS_VERSION_, '1.5', 'ge') || $this->registerHook('shoppingCartExtra')) &&
            (version_compare(_PS_VERSION_, '1.5', 'lt') || $this->registerHook('shoppingCart')) &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('adminOrder') &&
            Configuration::updateValue('PAYU_ENVIRONMENT', 'sandbox') &&
            Configuration::updateValue('PAYU_SANDBOX_POS_ID', '') &&
            Configuration::updateValue('PAYU_SANDBOX_POS_AUTH_KEY', '') &&
            Configuration::updateValue('PAYU_SANDBOX_CLIENT_SECRET', '') &&
            Configuration::updateValue('PAYU_SANDBOX_SIGNATURE_KEY', '') &&
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
            Configuration::updateValue('PAYU_PAYMENT_STATUS_REJECTED', 7) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_DELIVERED', 5)
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->deleteOrderStatussesOnUinstall() ||
            !Configuration::deleteByName('PAYU_PAYMENT_PLATFORM') ||
            !Configuration::deleteByName('PAYU_NAME') ||
            !Configuration::deleteByName('PAYU_ENVIRONMENT') ||
            !Configuration::deleteByName('PAYU_SANDBOX_POS_ID') ||
            !Configuration::deleteByName('PAYU_SANDBOX_POS_AUTH_KEY') ||
            !Configuration::deleteByName('PAYU_SANDBOX_CLIENT_SECRET') ||
            !Configuration::deleteByName('PAYU_SANDBOX_SIGNATURE_KEY') ||
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
            !Configuration::deleteByName('PAYU_ONE_STEP_CHECKOUT') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_PENDING') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_SENT') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_COMPLETED') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_CANCELED') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_REJECTED') ||
            !Configuration::deleteByName('PAYU_PAYMENT_STATUS_DELIVERED') ||
            !Configuration::deleteByName('PAYU_PAYMENT_ADVERT') ||
            !Configuration::deleteByName('PAYU_PAYMENT_BUTTON')
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
        if (Validate::isInt(Configuration::get($state)) ^ (Validate::isLoadedObject($order_state = new OrderState(Configuration::get($state))))) {
            $order_state = new OrderState();

            if (!empty($names)) {
                foreach ($names as $code => $name)
                    $order_state->name[Language::getIdByIso($code)] = $name;
            }

            $order_state->send_email = false;
            $order_state->invoice = false;
            $order_state->unremovable = false;
            $order_state->color = '#00AEEF';

            if (!$order_state->add() ||
                !Configuration::updateValue($state, $order_state->id) || !Configuration::updateValue($state, $order_state->id)
            )
                return false;

            copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');

            return $order_state->id;
        }

        return false;
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
     * @return bool
     */
    private function deleteOrderStatussesOnUinstall()
    {
        if (Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
        FROM " . _DB_PREFIX_ . "configuration
        WHERE name =  'PAYU_PAYMENT_STATUS_SENT' )") &&

            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
        FROM " . _DB_PREFIX_ . "configuration
        WHERE name =  'PAYU_PAYMENT_STATUS_SENT' )") &&

            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
        FROM " . _DB_PREFIX_ . "configuration
        WHERE name =  'PAYU_PAYMENT_STATUS_PENDING' )") &&

            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
        FROM " . _DB_PREFIX_ . "configuration
        WHERE name =  'PAYU_PAYMENT_STATUS_PENDING' )")
        ) return true;

    }


    /**
     * @return string
     */
    public function getContent()
    {
        $output = null;
        $errors = array();

        if (Tools::isSubmit('submit' . $this->name)) {
            if (!Configuration::updateValue('PAYU_ENVIRONMENT', Tools::getValue('PAYU_ENVIRONMENT')) ||
                !Configuration::updateValue('PAYU_PAYMENT_PLATFORM', Tools::getValue('PAYU_PAYMENT_PLATFORM')) ||
                !Configuration::updateValue('PAYU_SELF_RETURN', (int)Tools::getValue('PAYU_SELF_RETURN')) ||
                !Configuration::updateValue('PAYU_VALIDITY_TIME', Tools::getValue('PAYU_VALIDITY_TIME')) ||
                !Configuration::updateValue('PAYU_ONE_STEP_CHECKOUT', (int)Tools::getValue('PAYU_ONE_STEP_CHECKOUT')) ||
                !Configuration::updateValue('PAYU_SANDBOX_POS_ID', Tools::getValue('PAYU_SANDBOX_POS_ID')) ||
                !Configuration::updateValue('PAYU_SANDBOX_POS_AUTH_KEY', Tools::getValue('PAYU_SANDBOX_POS_AUTH_KEY')) ||
                !Configuration::updateValue('PAYU_SANDBOX_CLIENT_ID', Tools::getValue('PAYU_SANDBOX_CLIENT_ID')) ||
                !Configuration::updateValue('PAYU_SANDBOX_CLIENT_SECRET', Tools::getValue('PAYU_SANDBOX_CLIENT_SECRET')) ||
                !Configuration::updateValue('PAYU_SANDBOX_SIGNATURE_KEY', Tools::getValue('PAYU_SANDBOX_SIGNATURE_KEY')) ||
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
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_DELIVERED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_DELIVERED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_BUTTON', Tools::getValue('PAYU_PAYMENT_BUTTON')) ||
                !Configuration::updateValue('PAYU_PAYMENT_ADVERT', Tools::getValue('PAYU_PAYMENT_ADVERT')) ||

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
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $lang_iso_code = Language::getIsoById($default_lang);

        $media = $this->getMediaResourcesList($lang_iso_code);

        // Load current value
        $this->context->smarty->assign(array(
            'PAYU_PAYMENT_PLATFORM_EPAYMENT' => self::BUSINESS_PARTNER_TYPE_EPAYMENT,
            'PAYU_PAYMENT_PLATFORM_PLATNOSCI' => self::BUSINESS_PARTNER_TYPE_PLATNOSCI,
            'PAYU_PAYMENT_PLATFORM' => Configuration::get('PAYU_PAYMENT_PLATFORM'),
            'PAYU_PAYMENT_PLATFORM_OPTIONS' => $this->getBusinessPartnersList(),
            'PAYU_ENVIRONMENT' => Configuration::get('PAYU_ENVIRONMENT'),
            'PAYU_ENVIRONMENT_OPTIONS' => array(
                array(
                    'id' => 'sandbox',
                    'name' => $this->l('Yes')
                ),
                array(
                    'id' => 'secure',
                    'name' => $this->l('No')
                )
            ),
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
            'PAYU_SANDBOX_POS_ID' => Configuration::get('PAYU_SANDBOX_POS_ID'),
            'PAYU_SANDBOX_POS_AUTH_KEY' => Configuration::get('PAYU_SANDBOX_POS_AUTH_KEY'),
            'PAYU_SANDBOX_CLIENT_ID' => Configuration::get('PAYU_SANDBOX_CLIENT_ID'),
            'PAYU_SANDBOX_CLIENT_SECRET' => Configuration::get('PAYU_SANDBOX_CLIENT_SECRET'),
            'PAYU_SANDBOX_SIGNATURE_KEY' => Configuration::get('PAYU_SANDBOX_SIGNATURE_KEY'),
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
            'PAYU_PAYMENT_STATUS_DELIVERED' => Configuration::get('PAYU_PAYMENT_STATUS_DELIVERED'),
            'PAYU_PAYMENT_BUTTON' => Configuration::get('PAYU_PAYMENT_BUTTON'),
            'PAYU_PAYMENT_BUTTON_OPTIONS' => $this->getMediaButtonsResourcesList($media),
            'PAYU_PAYMENT_ADVERT' => Configuration::get('PAYU_PAYMENT_ADVERT'),
            'PAYU_PAYMENT_ADVERT_OPTIONS' => $this->getMediaAdvertsResourcesList($media)
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
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $lang_iso_code = Language::getIsoById($default_lang);

        $media = $this->getMediaResourcesList($lang_iso_code);

        $output = '<script type="text/javascript">var business_platforms = ' . Tools::jsonEncode($this->getBusinessPartnersPayU($media)) . ';</script>';
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
                    case Configuration::get('PAYU_PAYMENT_STATUS_DELIVERED'):
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
                    case Configuration::get('PAYU_PAYMENT_STATUS_DELIVERED'):
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
                /*if (!$order->hasInvoice())
                    $use_existings_payment = true;*/
                $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_DELIVERED'), $id_order, $use_existings_payment);
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
     * @param $lang_iso_code
     * @return stdObject
     */
    public function getMediaResourcesList($lang_iso_code)
    {
        $media_resources = $this->mediaOpenPayU($lang_iso_code);

        return $media_resources;
    }

    /**
     * @param $media_resources
     * @return array|null
     */
    public function getMediaButtonsResourcesList($media_resources)
    {
        $list = array();

        if (empty($media_resources->buttons))
            return null;

        foreach ($media_resources->buttons as $button)
            $list[] = array('id' => $button, 'name' => $button);

        return $list;
    }

    /**
     * @param $media_resources
     * @return array|null
     */
    public function getMediaAdvertsResourcesList($media_resources)
    {
        $list = array();

        if (empty($media_resources->adverts))
            return null;

        foreach ($media_resources->adverts as $group) {
            foreach ($group as $advert)
                $list[] = array('id' => $advert, 'name' => $advert);
        }

        return $list;
    }

    /**
     * Return PayU business partners
     *
     * @return array
     */
    private function getBusinessPartnersPayU()
    {
        $business_partners = $this->jsonOpenPayU('business_partners');
        $business_partners = Tools::jsonDecode(Tools::jsonEncode($business_partners), true);

        if (empty($business_partners))
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
    public function hookDisplayRightColumn()
    {
        return $this->hookDisplayLeftColumn();
    }

    /**
     * @return mixed
     */
    public function hookDisplayLeftColumn()
    {
        $img = Configuration::get('PAYU_PAYMENT_ADVERT');

        if (Configuration::get('PS_SSL_ENABLED'))
            $img = str_replace('http://', 'https://', $img);

        $this->context->smarty->assign('image', $img);

        return $this->fetchTemplate('/views/templates/hook/advertisement.tpl');
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'css/payu.css', 'all');

        if (Tools::getValue('payu_order_error'))
            return sprintf('<script>alert(%s);</script>', ToolsCore::jsonEncode($this->l('An error occurred when processing the order')));
    }

    /**
     * @return mixed
     */
    public function hookPayment()
    {
        $img = Configuration::get('PAYU_PAYMENT_BUTTON');

        if (Configuration::get('PS_SSL_ENABLED'))
            $img = str_replace('http://', 'https://', $img);

        if (version_compare(_PS_VERSION_, '1.5', 'lt'))
            $link = $this->getModuleAddress() . 'backward_compatibility/payment.php';
        else
            $link = $this->context->link->getModuleLink('payu', 'payment');

        $this->context->smarty->assign(array('image' => $img, 'actionUrl' => $link));

        if (version_compare(_PS_VERSION_, '1.6', 'lt'))
            $template = $this->fetchTemplate('/views/templates/hook/payment.tpl');
        else
            $template = $this->fetchTemplate('/views/templates/hook/payment16.tpl');

        return $template;
    }

    /**
     * @return mixed
     */
    public function hookShoppingCartExtra()
    {
        return $this->hookShoppingCart();
    }

    /**
     * @return mixed
     */
    public function hookShoppingCart()
    {
        $img = Configuration::get('PAYU_PAYMENT_BUTTON');

        $this->context->smarty->assign(array('image' => $img));

        return $this->fetchTemplate('/views/templates/hook/cart.tpl');
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

        if (Tools::isSubmit('submitpayustatus') && $this->payu_order_id && $order_payment['status'] == self::PAYMENT_STATUS_SENT) {
            if (trim(Tools::getValue('PAYU_PAYMENT_STATUS')) &&
                $this->sendPaymentUpdate(trim(Tools::getValue('PAYU_PAYMENT_STATUS')))
            )
                $output .= $this->displayConfirmation($this->l('Update status request has been sent'));
            else
                $output .= $this->displayError($this->l('Update status request has not been completed correctly.'));
        }

        $this->context->smarty->assign(array(
            'PAYU_PAYMENT_STATUS_OPTIONS' => $this->getPaymentAcceptanceStatusesList(),
            'PAYU_PAYMENT_STATUS' => $order_payment['status']
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
                    "orderStatus" => $status
                );
                $result = OpenPayU_Order::statusUpdate($status_update);
            }

            if ($result->getSuccess()) {
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
     * Return PayU json data
     *
     * @param string|null $section Section to return from json, or null to return all
     * @param string $lang language ISO code
     * @return stdClass
     */
    private function jsonOpenPayU($section = null, $lang = 'en')
    {
        static $cache = array();

        if (!isset($cache[$lang])) {
            $url = 'http://openpayu.com/' . trim($lang) . '/goods/json';

            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, $url);
            curl_setopt($c, CURLOPT_POST, 0);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            $content = curl_exec($c);
            $curl_info = curl_getinfo($c);
            curl_close($c);

            if ($curl_info['http_code'] != 200 && $lang != 'en')
                return $this->mediaOpenPayU('en');

            $cache[$lang] = Tools::jsonDecode($content);
        }

        return $section === null ? $cache[$lang] : (isset($cache[$lang]->$section) ? $cache[$lang]->$section : null);
    }

    /**
     * Return PayU media data from json
     *
     * @param string $lang
     * @return stdClass
     */
    private function mediaOpenPayU($lang = 'en')
    {
        return $this->jsonOpenPayU('media', $lang);
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
     * Convert to decimal
     *
     * @param $val
     * @return float
     */
    private function toDecimal($val)
    {
        return ($val / 100);
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
            array('id' => self::ORDER_STATUS_COMPLETE, 'name' => 'Payment accepted'),
            array('id' => self::ORDER_STATUS_CANCEL, 'name' => 'Payment rejected')
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
        SimplePayuLogger::addLog('order', __FUNCTION__, $this->l('Entrance'), $this->payu_order_id);
        $currency = Currency::getCurrency($this->cart->id_currency);
        $return_array = array();

        $items = array();

        $cart_products = $this->cart->getProducts();

        //discounts and cart rules
        list($items, $total) = $this->getDiscountsAndCartRules($items, $cart_products);

        // Wrapping fees
        list($wrapping_fees_tax_inc, $items, $total) = $this->getWrappingFees($items, $total);

        $carriers_list = $this->getCarriersListForCart($this->cart);
        $grand_total = $this->getGrandTotal($wrapping_fees_tax_inc, $total);
        list($order_complete_link, $order_notify_link, $order_cancel_link) = $this->getLinks();
        if (!empty($this->cart->id_customer)) {
            $customer = new Customer((int)$this->cart->id_customer);

            if ($customer->email) {
                $customer_sheet = $this->getCustomer($customer);

            }
        }

        //prepare data for OrderCreateRequest
        $ocreq = $this->prepareOrder($items, $customer_sheet, $order_notify_link, $order_cancel_link, $order_complete_link, $currency, $grand_total, $carriers_list);

        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OrderCreateRequest: ', $this->payu_order_id);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id);
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OrderCreateResponse: ', $this->payu_order_id);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id);
            if ($result->getStatus() == 'SUCCESS') {
                $context = Context::getContext();
                $context->cookie->__set('payu_order_id', $result->getResponse()->orderId);

                $return_array = array(
                    'redirectUri' => urldecode($result->getResponse()->redirectUri),
                    'sessionId' => $result->getResponse()->orderId
                );
            } else {
                SimplePayuLogger::addLog('order', __FUNCTION__, 'OpenPayU_Order::create($ocreq) doesnt return SUCCESS! ' . $this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage(), $this->payu_order_id));
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage()), 1);
            }

        } catch (Exception $e) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Exception catched! ' . $this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()));
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()), 1);
        }
        SimplePayuLogger::addLog('order', __FUNCTION__, print_r($return_array, true));
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
     * @return array
     */
    public function getCarriersListForCart()
    {
        $carrier_list = array();

        //$currency = Currency::getCurrency($this->cart->id_currency);
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
                    //$price_tax_exc = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                    //? 0 : $this->cart->getOrderShippingCost((int)$this->cart->id_carrier, false, $country, $cart_products));
                } else {
                    $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                        ? 0 : $this->cart->getPackageShippingCost((int)$this->cart->id_carrier, true, $country, $cart_products));
                    //$price_tax_exc = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                    //? 0 : $this->cart->getPackageShippingCost((int)$this->cart->id_carrier, false, $country, $cart_products));
                }

                //$tax_amount = $price - $price_tax_exc;
            } else {
                $price = 0;
                //$price_tax_exc = 0;
                //$tax_amount = 0;
            }

            if ((int)$selected_carrier->active == 1) {

                $carrier_list['shippingMethods'][] = array(
                    'name' => $selected_carrier->name . ' (' . $selected_carrier->id . ')',
                    'country' => $country->iso_code,
                    'price' => $this->toAmount($price)
                );

            }
        } else {
            $i = 0;
            if ((int)$this->context->cookie->id_customer > 0) {
                $customer = new Customer((int)$this->context->cookie->id_customer);
                $address = new Address((int)$this->cart->id_address_delivery);
                $id_zone = Address::getZoneById((int)$address->id);
                $carriers = Carrier::getCarriersForOrder($id_zone, $customer->getGroups());
            } else
                $carriers = Carrier::getCarriers((int)$this->cart->id_lang, true);

            if ($carriers) {
                foreach ($carriers as $carrier) {
                    $c = new Carrier((int)$carrier['id_carrier']);

                    $shipping_method = $c->getShippingMethod();

                    if ($free_shipping == false) {
                        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                            $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                                ? 0 : $this->cart->getOrderShippingCost((int)$carrier['id_carrier'], true, $country, $cart_products));
                            //$price_tax_exc = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                            //? 0 : $this->cart->getOrderShippingCost((int)$carrier['id_carrier'], false, $country, $cart_products));
                        } else {
                            $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                                ? 0 : $this->cart->getPackageShippingCost((int)$carrier['id_carrier'], true, $country, $cart_products));
                            //$price_tax_exc = ($shipping_method == Carrier::SHIPPING_METHOD_FREE
                            //? 0 : $this->cart->getPackageShippingCost((int)$carrier['id_carrier'], false, $country, $cart_products));
                        }
                        //$tax_amount = $price - $price_tax_exc;
                    } else {
                        $price = 0;
                        //$price_tax_exc = 0;
                        //$tax_amount = 0;
                    }

                    if ($carrier['id_carrier'] != $this->cart->id_carrier) {
                        if ((int)$carrier['active'] == 1) {

                            $carrier_list['shippingMethods'][] = array(
                                'name' => $carrier['name'] . ' (' . $carrier['id_carrier'] . ')',
                                'country' => $country->iso_code,
                                'price' => $this->toAmount($price)
                            );

                            $i++;
                        }
                    }
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
        $result = Db::getInstance()->getRow('
			SELECT `id_order` FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"');

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
        $result = Db::getInstance()->getRow('
			SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"');

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
        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('DB update: ') . 'order_payu_payments ' . $this->l('status changed to: ') . $status, $this->payu_order_id);
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
        SimplePayuLogger::addLog('order', __FUNCTION__, $this->l('DB Insert ') . $this->l('Prestashop order id: ') . $this->id_order . $this->l('Prestashop cart id: ') . $this->id_cart, $this->payu_order_id);
        if (Db::getInstance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'order_payu_payments` (`id_order`, `id_cart`, `id_session`,  `status`,  `create_at`)
				VALUES ("' . (int)$this->id_order . '", "' . (int)$this->id_cart . '",  "' . $this->payu_order_id . '",   "' . addslashes($status) . '", NOW())')
        )
            return (int)Db::getInstance()->Insert_ID();

        return false;
    }

    /**
     * @param $iso_country_code
     * @return null|string
     */
    public function shippingCostRetrieveRequest($iso_country_code)
    {
        if ($iso_country_code) {
            $cart = new Cart($this->id_cart);

            if ($id_country = Country::getByIso($iso_country_code)) {
                if ($id_zone = Country::getIdZone($id_country)) {
                    $carriers = Carrier::getCarriersForOrder($id_zone);
                    $currency = Currency::getCurrency($cart->id_currency);
                    if ($carriers) {
                        $carrier_list = array();
                        foreach ($carriers as $carrier) {
                            $c = new Carrier((int)$carrier['id_carrier']);
                            $shipping_method = $c->getShippingMethod();

                            $price = ($shipping_method == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier']));
                            $price_tax_exc = ($shipping_method == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier'], false));

                            $carrier_list[]['ShippingCost'] = array(
                                'Type' => $carrier['name'] . ' (' . $carrier['id_carrier'] . ')',
                                'CountryCode' => Tools::strtoupper($iso_country_code),
                                'Price' => array(
                                    'Gross' => $this->toAmount($price),
                                    'Net' => $this->toAmount($price_tax_exc),
                                    'Tax' => $this->toAmount($price) - $this->toAmount($price_tax_exc),
                                    'CurrencyCode' => Tools::strtoupper($currency['iso_code'])
                                )
                            );
                        }

                        $shipping_cost = array(
                            'CountryCode' => Tools::strtoupper($iso_country_code),
                            'ShipToOtherCountry' => 'true',
                            'ShippingCostList' => $carrier_list
                        );
                        $xml = OpenPayU::buildShippingCostRetrieveResponse($shipping_cost, $this->id_request, $iso_country_code);
                        return $xml;
                    } else
                        Logger::addLog('carrier by id_zone is undefined');
                }
            }
        }

        return null;
    }

    /**
     * @param $status
     * @return bool
     */
    private function updateOrderState($status)
    {
        if ($status === self::ORDER_V2_COMPLETED) {
            if (!$this->status_completed) {
                SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('I havent been here yet, i will mark my territory! No other thread should go further! '), $this->payu_order_id);
                $this->status_completed = true;
            } else {
                SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Status is already completed, Im getting out of here! '), $this->payu_order_id);
                exit;
            }
        }
        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Entrance '), $this->payu_order_id);
        if (!empty($this->order->id) && !empty($status)) {
            SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Payu order status: ') . $status, $this->payu_order_id);
            $order_state_id = $this->getCurrentPrestaOrderState();

            switch ($status) {
                //case self::PAYMENT_STATUS_END :
                case self::ORDER_V2_COMPLETED :
//					if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'))
                    if (!$this->checkIfStatusCompleted($this->payu_order_id) && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->order->id);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Addition to Prestashop order status history: ') . Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->payu_order_id);
                        $history->addWithemail(true);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Email sent'), $this->payu_order_id);

                    }
                    break;
                //case self::PAYMENT_STATUS_CANCEL :
                case self::ORDER_V2_CANCELED :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    break;
                //case self::PAYMENT_STATUS_REJECT :
                case self::ORDER_V2_REJECTED :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_REJECTED')) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_REJECTED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    break;
                //case self::PAYMENT_STATUS_SENT :
                case self::ORDER_V2_PENDING :
                    if ($order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')
                        && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_SENT')
                    ) {
                        $history = new OrderHistory();
                        $history->id_order = $this->order->id;
                        $history->date_add = date('Y-m-d H:i:s');
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_SENT'), $this->order->id);
                        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Addition to Prestashop order status history: ') . Configuration::get('PAYU_PAYMENT_STATUS_SENT'), $this->payu_order_id);

                        $history->addWithemail(false);
                    }
                    break;
            }
            SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Check if status is completed in DB: '), $this->payu_order_id);
            if (!$this->checkIfStatusCompleted($this->payu_order_id)) {
                SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Status in DB is not COMPLETED, going to change status id DB '), $this->payu_order_id);
                return $this->updateOrderPaymentStatusBySessionId($status);
            }
        }

        return false;
    }

    /**
     *
     */
    public function updateOrderData($response_notification)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Entrance:'), $this->payu_order_id);
        if (empty($this->payu_order_id))
            Logger::addLog($this->displayName . ' ' . $this->l('Can not get order information - id_session is empty'), 1);

//        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Preparing to OpenPayU_Order::retrieve'), $this->payu_order_id);
//        $result = OpenPayU_Order::retrieve($this->payu_order_id);
//        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Done OpenPayU_Order::retrieve'), $this->payu_order_id);

        $result = $response_notification;

        $response = $result;
//        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($response, true), $this->payu_order_id);
        if (isset($response->order)) {

            if (!empty($this->id_order)) {
                $this->order = new Order($this->id_order);


                // Delivery address add
                if (!empty($response->order->buyer)) {

                    $buyer = $response->order->buyer;

                    if (isset($buyer->phone) && !empty($buyer->phone))
                        $buyer->delivery->{'recipientPhone'} = $buyer->phone;

                    $new_delivery_address_id = $this->addNewAddress($buyer->delivery);

                    if (!empty($new_delivery_address_id))
                        $this->order->id_address_delivery = $new_delivery_address_id;

                    // Invoice address add
                    if (isset($response->order->buyer->invoice)) {
                        if (isset($buyer->phone) && !empty($buyer->phone))
                            $buyer->invoice->{'recipientPhone'} = $buyer->phone;

                        $new_invoice_address_id = $this->addNewAddress($buyer->invoice);

                        if (!empty($new_invoice_address_id))
                            $this->order->id_address_invoice = $new_invoice_address_id;
                    }

                }
                SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Order exists in PayU system '), $this->payu_order_id);
                if ($this->getCurrentPrestaOrderState() != 2) {
                    SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Prestashop order statusIS NOT COMPLETED, go to status actualization'), $this->payu_order_id);
                    if ($this->order->update()) {
                        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Prestashop actualized order status, go to PayU status actualization to: ') . $response->order->status, $this->payu_order_id);
                        $this->updateOrderState(isset($response->order->status) ? $response->order->status : null);
                    }
                }
            }
        }
    }

    /**
     * @param $address
     *
     * @return int
     */
    private function checkIsAddressExists($address)
    {
        $check_address = Db::getInstance()->executeS('
			SELECT `id_address`
			FROM `' . _DB_PREFIX_ . 'address`
			WHERE `id_customer` = "' . (int)$address->id_customer . '"
			AND `id_country` = "' . (int)$address->id_country . '"
			AND `company` = "' . addslashes($address->company) . '"
			AND `lastname` = "' . addslashes($address->lastname) . '"
			AND `firstname` = "' . addslashes($address->firstname) . '"
			AND `address1` = "' . addslashes($address->address1) . '"
			AND `address2` = "' . addslashes($address->address2) . '"
			AND `postcode` = "' . addslashes($address->postcode) . '"
			AND `city` = "' . addslashes($address->city) . '"
			AND `phone` = "' . addslashes($address->phone) . '"
			AND `phone_mobile` = "' . addslashes($address->phone_mobile) . '"
			AND `vat_number` = "' . addslashes($address->vat_number) . '"
			ORDER BY id_address DESC LIMIT 0,1
		');

        $id_address = array_shift($check_address);

        return (int)$id_address['id_address'];
    }

    /**
     * @param $address
     * @return mixed
     */
    private function addNewAddress($address)
    {
        if ((int)Country::getByIso($address->{'countryCode'}))
            $address_country_id = Country::getByIso($address->{'countryCode'});
        else
            $address_country_id = Configuration::get('PS_COUNTRY_DEFAULT');

        $address->{'recipientName'} = preg_replace('/\s+/', ' ', $address->{'recipientName'});
        $shipping_recipient_name = explode(' ', $address->{'recipientName'});

        $new_address = new Address();
        $new_address->id_customer = (int)$this->order->id_customer;
        $new_address->id_country = (int)$address_country_id;
        $new_address->id_state = 0;
        $new_address->alias = $this->l('PayU address') . ': ' . $this->order->id . ' ' . time();
        $new_address->firstname = $shipping_recipient_name[0];
        $new_address->lastname = $shipping_recipient_name[1];

        $new_address->address1 = trim($address->street);
        $new_address->postcode = $address->{'postalCode'};
        if (!empty($address->city))
            $new_address->city = $address->city;
        $new_address->phone = $address->{'recipientPhone'};
        if (isset($address->tin))
            $new_address->vat_number = $address->tin;
        $new_address->deleted = 0;

        if ($id_address = $this->checkIsAddressExists($new_address))
            return $id_address;

        $new_address->add();
        return $new_address->id;
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
        SimplePayuLogger::addLog('notification', __FUNCTION__, $this->l('Addition of PAYMENT_ID info'), $this->payu_order_id);

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
        if ($this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)) + $wrapping_fees_tax_inc < $total) {
            $grand_total = $total;
            return $grand_total;
            //$discount_total = $total - $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING));
        } else {
            $grand_total = $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)) + $wrapping_fees_tax_inc;
            return $grand_total;
            //$discount_total = 0;
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
     * @param $carriers_list
     * @return array
     */
    private function prepareOrder($items, $customer_sheet, $order_notify_link, $order_cancel_link, $order_complete_link, $currency, $grand_total, $carriers_list)
    {
        $ocreq = array();

        $ocreq['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $ocreq['orderUrl'] = $this->context->link->getPageLink('guest-tracking.php', true);
        $ocreq['description'] = $this->l('Order for cart: ') . ' ' . $this->cart->id . ' ' . $this->l(' from the store: ') . ' ' . Configuration::get('PS_SHOP_NAME');
        $ocreq['validityTime'] = 60 * (int)Configuration::get('PAYU_VALIDITY_TIME');
        $ocreq['products'] = $items['products'];
        $ocreq['buyer'] = $customer_sheet;
        $ocreq['customerIp'] = (
        ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR']
        );
        $ocreq['notifyUrl'] = $order_notify_link;
        $ocreq['cancelUrl'] = $order_cancel_link;
        $ocreq['continueUrl'] = $order_complete_link . '?id_cart=' . $this->cart->id;
        $ocreq['currencyCode'] = $currency['iso_code'];
        $ocreq['totalAmount'] = $grand_total;
        $ocreq['extOrderId'] = $this->cart->id . '-' . microtime();
        $ocreq['shippingMethods'] = $carriers_list['shippingMethods'];
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
                    'unitPrice' => $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING))
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
                    'unitPrice' => $this->toAmount($this->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING))
                );
                return array($items, $total);
            } else {
                $items['products'] = $this->addProductsToOrder($cart_products, $total);
                return array($items, $total);
            }
        }
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
