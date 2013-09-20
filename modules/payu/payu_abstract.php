<?php
/**
 *  ver. 1.9.10
 *  PayU Payment Modules
 *
 * @copyright  Copyright 2012 by PayU
 * @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *  http://www.payu.com
 *  http://twitter.com/openpayu
 */

if (!defined('_PS_VERSION_'))
    exit;

class PayUAbstract extends PaymentModule
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

    private $payu_status_complete = 2;
    private $payu_status_pending = 13;
    private $payu_status_cancel = 6;
    private $payu_status_reject = 7;
    private $payu_status_sent = 14;

    private $payu_environment;
    private $payu_button;
    private $payu_logo;
    private $payu_img_accept;
    private $payu_img_advert;
    private $payu_self_return;
    private $payu_pos_id;
    private $payu_oauth_client_name;
    private $payu_oauth_client_secret;
    private $payu_pos_auth_key;
    private $payu_signature_key;
    private $payu_pos_id_sandbox;
    private $payu_oauth_client_name_sandbox;
    private $payu_oauth_client_secret_sandbox;
    private $payu_pos_auth_key_sandbox;
    private $payu_signature_key_sandbox;
    private $payu_validity_time;
    private $payu_one_step_checkout;
    private $payu_ship_abroad;
    private $myUrl;

    public function __construct()
    {
        global $cookie;

        $this->name = 'payu';
        $this->tab = 'payments_gateways';
        $this->author = 'PayU';
        $this->version = '1.9.10';

        $this->info_url = 'http://www.payu.pl';

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('PayU');
        $this->description = $this->l('Accept PayU payments');

        $this->initializeOpenPayUConfiguration();

        $this->lang = Tools::strtolower(Language::getIsoById(intval($cookie->id_lang)));
    }

    /**
     * Install and register on hook
     */
    public function install()
    {
        if (!parent::install()
            OR !Configuration::updateValue('PAYU_ACTIVE_ENVIRONMENT', '')
            OR !Configuration::updateValue('PAYU_BUTTON', '')
            OR !Configuration::updateValue('PAYU_LOGO', '')
            OR !Configuration::updateValue('PAYU_IMG_ACCEPT', '')
            OR !Configuration::updateValue('PAYU_IMG_ADVERT', '')
            OR !Configuration::updateValue('PAYU_SELF_RETURN', '')
            OR !Configuration::updateValue('PAYU_POS_ID', '')
            OR !Configuration::updateValue('PAYU_OAUTH_CLIENT_NAME', '')
            OR !Configuration::updateValue('PAYU_OAUTH_CLIENT_SECRET', '')
            OR !Configuration::updateValue('PAYU_POS_AUTH_KEY', '')
            OR !Configuration::updateValue('PAYU_SIGNATURE_KEY', '')
            OR !Configuration::updateValue('PAYU_POS_ID_SANDBOX', '')
            OR !Configuration::updateValue('PAYU_OAUTH_CLIENT_NAME_SANDBOX', '')
            OR !Configuration::updateValue('PAYU_OAUTH_CLIENT_SECRET_SANDBOX', '')
            OR !Configuration::updateValue('PAYU_POS_AUTH_KEY_SANDBOX', '')
            OR !Configuration::updateValue('PAYU_SIGNATURE_KEY_SANDBOX', '')
            OR !Configuration::updateValue('PAYU_VALIDITY_TIME', '')
            OR !Configuration::updateValue('PAYU_ONE_STEP_CHECKOUT', '')
            OR !Configuration::updateValue('PAYU_SHIP_ABROAD', '')
            OR !Configuration::updateValue('PAYMENT_PAYU_STATUS_COMPLETE', '2')
            OR !Configuration::updateValue('PAYMENT_PAYU_STATUS_PENDING', '13')
            OR !Configuration::updateValue('PAYMENT_PAYU_STATUS_CANCEL', '6')
            OR !Configuration::updateValue('PAYMENT_PAYU_STATUS_REJECT', '7')
            OR !Configuration::updateValue('PAYMENT_PAYU_STATUS_SENT', '14')
            OR (!$this->registerHook('leftColumn') OR !$this->registerHook('rightColumn'))
            OR !$this->registerHook('header')
            OR !$this->registerHook('payment')
            OR !$this->registerHook('paymentReturn')
            OR !$this->registerHook('adminOrder')
            OR !$this->createSessionTable()
        )
            return false;

        if ((_PS_VERSION_ >= '1.5') && !$this->registerHook('shoppingCartExtra'))
            return false;
        elseif ((_PS_VERSION_ < '1.5') && !$this->registerHook('shoppingCart'))
            return false;

        if (Validate::isInt(Configuration::get('PAYMENT_PAYU_NEW_STATE')) XOR (Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENT_PAYU_NEW_STATE'))))) {
            $order_state_new = new OrderState();
            $order_state_new->name[Language::getIdByIso("pl")] = "Rozpoczęcie płatności PayU";
            $order_state_new->name[Language::getIdByIso("en")] = "Payment PayU start";
            $order_state_new->send_email = false;
            $order_state_new->invoice = false;
            $order_state_new->unremovable = false;
            $order_state_new->color = "lightblue";
            if (!$order_state_new->add())
                return false;
            if (!Configuration::updateValue('PAYMENT_PAYU_NEW_STATE', $order_state_new->id) || !Configuration::updateValue('PAYU_ORDER_STATUS_PENDING', $order_state_new->id))
                return false;
        }

        $order_state_new = null;

        if (Validate::isInt(Configuration::get('PAYMENT_PAYU_AWAITING_STATE')) XOR (Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENT_PAYU_AWAITING_STATE'))))) {
            $order_state_new = new OrderState();
            $order_state_new->name[Language::getIdByIso("pl")] = "Płatność oczekuje na odbiór PayU";
            $order_state_new->name[Language::getIdByIso("en")] = "Payment PayU awaiting reception.";
            $order_state_new->send_email = false;
            $order_state_new->invoice = false;
            $order_state_new->unremovable = false;
            $order_state_new->color = "lightblue";
            if (!$order_state_new->add())
                return false;
            if (!Configuration::updateValue('PAYMENT_PAYU_AWAITING_STATE', $order_state_new->id) || !Configuration::updateValue('PAYMENT_PAYU_STATUS_SENT', $order_state_new->id))
                return false;
        }

        $order_state_new = null;

        return true;
    }

    /**
     * Uninstall and unregister on hook
     */
    public function uninstall()
    {
        if (!parent::uninstall()
            OR !Configuration::deleteByName('PLATNOSCI_POS_ID')
            OR !Configuration::deleteByName('PAYU_ACTIVE_ENVIRONMENT', '')
            OR !Configuration::deleteByName('PAYU_BUTTON', '')
            OR !Configuration::deleteByName('PAYU_LOGO', '')
            OR !Configuration::deleteByName('PAYU_IMG_ACCEPT', '')
            OR !Configuration::deleteByName('PAYU_IMG_ADVERT', '')
            OR !Configuration::deleteByName('PAYU_SELF_RETURN', '')
            OR !Configuration::deleteByName('PAYU_POS_ID', '')
            OR !Configuration::deleteByName('PAYU_OAUTH_CLIENT_NAME', '')
            OR !Configuration::deleteByName('PAYU_OAUTH_CLIENT_SECRET', '')
            OR !Configuration::deleteByName('PAYU_POS_AUTH_KEY', '')
            OR !Configuration::deleteByName('PAYU_SIGNATURE_KEY', '')
            OR !Configuration::deleteByName('PAYU_POS_ID_SANDBOX', '')
            OR !Configuration::deleteByName('PAYU_OAUTH_CLIENT_NAME_SANDBOX', '')
            OR !Configuration::deleteByName('PAYU_OAUTH_CLIENT_SECRET_SANDBOX', '')
            OR !Configuration::deleteByName('PAYU_POS_AUTH_KEY_SANDBOX', '')
            OR !Configuration::deleteByName('PAYU_SIGNATURE_KEY_SANDBOX', '')
            OR !Configuration::deleteByName('PAYU_VALIDITY_TIME', '')
            OR !Configuration::deleteByName('PAYU_ONE_STEP_CHECKOUT', '')
            OR !Configuration::deleteByName('PAYU_SHIP_ABROAD', '')
            OR !Configuration::deleteByName('PAYMENT_PAYU_STATUS_COMPLETE', '')
            OR !Configuration::deleteByName('PAYMENT_PAYU_STATUS_PENDING', '')
            OR !Configuration::deleteByName('PAYMENT_PAYU_STATUS_CANCEL', '')
            OR !Configuration::deleteByName('PAYMENT_PAYU_STATUS_REJECT', '')
            OR !Configuration::deleteByName('PAYMENT_PAYU_STATUS_SENT', '')
        )
            return false;

        return true;
    }

    /**
     * Create table with PayU order session
     */
    private function createSessionTable()
    {
        return Db::getInstance()->Execute
            (
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payu_session` (
			`id_payu_session` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`id_order` INT UNSIGNED NOT NULL,
			`id_cart` INT UNSIGNED NOT NULL,
			`sid`	varchar(64) NOT NULL,
			`status` varchar(64) NOT NULL,
			`create_at` datetime,
			`update_at` datetime
			)'
            );
    }

    /**
     * Convert to amount
     *
     * @param $val
     * @return int
     * */
    private function toAmount($val)
    {
        return (int)($val * 100);
    }

    /**
     * Convert to decimal
     *
     * @param $val
     * @return float
     * */
    private function toDecimal($val)
    {
        return ($val / 100);
    }

    /**
     * Create option with button
     *
     * @param $selected,$media graphics
     * @return html
     * */
    private function selectButtonGraphics($selected, $media)
    {
        $buttons = $media->buttons;
        $option = '';

        foreach ($buttons as $key => $button) {
            $option .= '<option ' . (($selected == $button) ? ' selected="selected"' : '') . '>' . $button . '</option>';
        }

        return $option;
    }

    /**
     * Create option with logo graphics
     *
     * @param $selected,$media
     * @return html
     * */
    private function selectLogoGraphics($selected, $media)
    {
        $logos = $media->logos;
        $option = '';

        foreach ($logos as $key => $logo) {
            $option .= '<option ' . (($selected == $logo) ? ' selected="selected"' : '') . '>' . $logo . '</option>';
        }

        return $option;
    }

    /**
     * Create option with adverts graphics
     *
     * @param $selected,$media
     * @return html
     * */
    private function selectAdvertsGraphics($selected, $media)
    {
        $adverts = $media->adverts;
        $option = '';

        foreach ($adverts as $advert) {
            foreach ($advert as $key => $img) {
                $option .= '<option ' . (($selected == $img) ? ' selected="selected"' : '') . '>' . $img . '</option>';
            }
        }

        return $option;
    }

    /**
     * Create option with accept graphics
     *
     * @param $selected
     * @return html
     * */
    private function selectAcceptGraphics($selected)
    {
        $option = '';
        $media = array('http://www.payu.pl/sites/default/files/pliki_graficzne/akceptujemy_payu.gif', 'http://www.payu.pl/sites/default/files/pliki_graficzne/akceptujemy_zaplacisz_payu.gif');

        foreach ($media as $key => $img) {
            $option .= '<option ' . (($selected == $img) ? ' selected="selected"' : '') . '>' . $img . '</option>';
        }

        return $option;
    }

    /**
     * Create option with validity time
     *
     * @param $selected
     * @return html
     * */
    private function selectValidityTime($selected)
    {

        $option = '';
        $minutes = array(
            '1440' => '1440 min (24h)',
            '720' => '720 min (12h)',
            '360' => '360 min (6h)',
            '60' => '60 min (1h)',
            '30' => '30 min',
        );

        foreach ($minutes as $key => $val) {
            $option .= '<option value="' . $key . '" ' . (($selected == $key) ? ' selected="selected"' : '') . '>' . $val . '</option>';
        }

        return $option;

    }

    /**
     * Create option with payu order status
     *
     * @param $selected
     * @return html
     * */
    private function selectStatus($selected)
    {

        $option = '';
        $minutes = array(
            'ORDER_STATUS_PENDING' => $this->l('Order status pending'),
            'ORDER_STATUS_SENT' => $this->l('Order status sent'),
            'ORDER_STATUS_COMPLETE' => $this->l('Order status complete'),
            'ORDER_STATUS_CANCEL' => $this->l('Order status cancel'),
            'ORDER_STATUS_REJECT' => $this->l('Order status reject')
        );

        foreach ($minutes as $key => $val) {
            $option .= '<option value="' . $key . '" ' . (($selected == $key) ? ' selected="selected"' : '') . '>' . $val . '</option>';
        }

        return $option;

    }


    /**
     * Load config from DB
     */
    private function loadConfiguration()
    {

        $this->myUrl = $this->getModuleAddress(true, true);

        $this->payu_environment = Configuration::get('PAYU_ACTIVE_ENVIRONMENT');
        $this->payu_button = Configuration::get('PAYU_BUTTON');
        $this->payu_logo = Configuration::get('PAYU_LOGO');
        $this->payu_img_accept = Configuration::get('PAYU_IMG_ACCEPT');
        $this->payu_img_advert = Configuration::get('PAYU_IMG_ADVERT');
        $this->payu_self_return = Configuration::get('PAYU_SELF_RETURN');
        $this->payu_pos_id = Configuration::get('PAYU_POS_ID');
        $this->payu_oauth_client_name = Configuration::get('PAYU_OAUTH_CLIENT_NAME');
        $this->payu_oauth_client_secret = Configuration::get('PAYU_OAUTH_CLIENT_SECRET');
        $this->payu_pos_auth_key = Configuration::get('PAYU_POS_AUTH_KEY');
        $this->payu_signature_key = Configuration::get('PAYU_SIGNATURE_KEY');
        $this->payu_pos_id_sandbox = Configuration::get('PAYU_POS_ID_SANDBOX');
        $this->payu_oauth_client_name_sandbox = Configuration::get('PAYU_OAUTH_CLIENT_NAME_SANDBOX');
        $this->payu_oauth_client_secret_sandbox = Configuration::get('PAYU_OAUTH_CLIENT_SECRET_SANDBOX');
        $this->payu_pos_auth_key_sandbox = Configuration::get('PAYU_POS_AUTH_KEY_SANDBOX');
        $this->payu_signature_key_sandbox = Configuration::get('PAYU_SIGNATURE_KEY_SANDBOX');
        $this->payu_validity_time = Configuration::get('PAYU_VALIDITY_TIME');
        $this->payu_one_step_checkout = Configuration::get('PAYU_ONE_STEP_CHECKOUT');
        $this->payu_ship_abroad = Configuration::get('PAYU_SHIP_ABROAD');

        $this->payu_status_complete = intval(Configuration::get('PAYMENT_PAYU_STATUS_COMPLETE'));
        $this->payu_status_pending = intval(Configuration::get('PAYMENT_PAYU_STATUS_PENDING'));
        $this->payu_status_cancel = intval(Configuration::get('PAYMENT_PAYU_STATUS_CANCEL'));
        $this->payu_status_reject = intval(Configuration::get('PAYMENT_PAYU_STATUS_REJECT'));
        $this->payu_status_sent = intval(Configuration::get('PAYMENT_PAYU_STATUS_SENT'));

    }

    public function fetchTemplate($path, $name, $extension = false)
    {
        global $smarty;

        return $smarty->fetch(_PS_MODULE_DIR_ . $this->name . $path . $name . '.' . ($extension ? $extension : 'tpl'));
    }

    /**
     * Get session id by cart id
     *
     * @param $sessionId
     * @return string
     * */
    private function getCartIdBySessionId($sessionId)
    {
        $sessArr = explode("-", $sessionId);
        if (count($sessArr) < 2)
            return null;
        return $sessArr[0];
    }

    /**
     * Get order ID by sessiond ID
     *
     * @param $sessionId
     * @return mixed
     */
    private function getOrderIdBySessionId($sessionId)
    {
        $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'payu_session` WHERE `sid` = "' . $sessionId . '"');
        return $id_order;
    }


    /**
     * POST Event handling
     */
    public function postProcess()
    {
        global $currentIndex;

        $errors = '';
        $update = false;

        if (Tools::isSubmit('submitPayU')) {
            $environment = Tools::getValue('environment');
            $self_return = (int)(Tools::getValue('self-return'));
            $validity_time = (int)(Tools::getValue('validity_time'));
            $ship_abroad = Tools::getValue('ship_abroad');
            $one_step = (int)(Tools::getValue('one_step'));
            Configuration::updateValue('PAYU_ACTIVE_ENVIRONMENT', $environment);
            Configuration::updateValue('PAYU_SELF_RETURN', $self_return);
            Configuration::updateValue('PAYU_VALIDITY_TIME', $validity_time);
            Configuration::updateValue('PAYU_SHIP_ABROAD', $ship_abroad);
            Configuration::updateValue('PAYU_ONE_STEP_CHECKOUT', $one_step);

            Configuration::updateValue('PAYMENT_PAYU_STATUS_PENDING', Tools::getValue('payu_status_pending'));
            Configuration::updateValue('PAYMENT_PAYU_STATUS_SENT', Tools::getValue('payu_status_sent'));
            Configuration::updateValue('PAYMENT_PAYU_STATUS_COMPLETE', Tools::getValue('payu_status_complete'));
            Configuration::updateValue('PAYMENT_PAYU_STATUS_CANCEL', Tools::getValue('payu_status_cancel'));
            Configuration::updateValue('PAYMENT_PAYU_STATUS_REJECT', Tools::getValue('payu_status_reject'));

            $oauth_client_name_sandbox = Tools::getValue('oauth_client_name_sandbox');
            $oauth_client_secret_sandbox = Tools::getValue('oauth_client_secret_sandbox');
            $pos_auth_key_sandbox = Tools::getValue('pos_auth_key_sandbox');
            $signature_key_sandbox = Tools::getValue('signature_key_sandbox');
            Configuration::updateValue('PAYU_POS_ID_SANDBOX', $oauth_client_name_sandbox);
            Configuration::updateValue('PAYU_OAUTH_CLIENT_NAME_SANDBOX', $oauth_client_name_sandbox);
            Configuration::updateValue('PAYU_OAUTH_CLIENT_SECRET_SANDBOX', $oauth_client_secret_sandbox);
            Configuration::updateValue('PAYU_POS_AUTH_KEY_SANDBOX', $pos_auth_key_sandbox);
            Configuration::updateValue('PAYU_SIGNATURE_KEY_SANDBOX', $signature_key_sandbox);


            $oauth_client_name = Tools::getValue('oauth_client_name');
            $oauth_client_secret = Tools::getValue('oauth_client_secret');
            $pos_auth_key = Tools::getValue('pos_auth_key');
            $signature_key = Tools::getValue('signature_key');
            Configuration::updateValue('PAYU_POS_ID', $oauth_client_name);
            Configuration::updateValue('PAYU_OAUTH_CLIENT_NAME', $oauth_client_name);
            Configuration::updateValue('PAYU_OAUTH_CLIENT_SECRET', $oauth_client_secret);
            Configuration::updateValue('PAYU_POS_AUTH_KEY', $pos_auth_key);
            Configuration::updateValue('PAYU_SIGNATURE_KEY', $signature_key);


            $payment_button = Tools::getValue('payment_button');
            $payment_logo = Tools::getValue('payment_logo');
            $payment_accept = Tools::getValue('payment_accept');
            $payment_advert = Tools::getValue('payment_advert');
            Configuration::updateValue('PAYU_BUTTON', $payment_button);
            Configuration::updateValue('PAYU_LOGO', $payment_logo);
            Configuration::updateValue('PAYU_IMG_ACCEPT', $payment_accept);
            Configuration::updateValue('PAYU_IMG_ADVERT', $payment_advert);
            $update = true;
        }


        if ($errors) {
            echo $this->displayError($errors);
        } else {
            if ($update)
                Tools::redirectAdmin($_SERVER['REQUEST_URI'] . '&conf=4');
        }
    }

    /**
     * Display admin form
     */
    public function getContent()
    {
        global $protocol_content, $cookie;

        $this->postProcess();
        $this->loadConfiguration();

        $lang = Tools::strtolower(Language::getIsoById(intval($cookie->id_lang)));
        $media = $this->mediaOpenPayU($lang);
        $upgrade = $this->upgradeOpenPayU($lang);
        $upgradeInfo = $this->upgradeOpenPayUInfo($upgrade['prestashop']['1.4.4']['info']);

        $statuses_array = array();

        if (_PS_VERSION_ < '1.5')
            $lang_id = (int)($cookie->id_lang);
        else
            $lang_id = (int)$this->context->language->id;

        $statuses = OrderState::getOrderStates($lang_id);

        foreach ($statuses as $status)
            $statuses_array[$status['id_order_state']] = $status['name'];

        $output = '';

        $output .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
					<fieldset>
                        <legend>' . $this->l('Main parameters') . '</legend>
                        <table>
                            <tr>
                                <td><label for="environment">' . $this->l('Test Mode On') . '</label></td>
                                <td>
                                    <select id="environment" name="environment">
                                        <option value="sandbox"' . (($this->payu_environment == 'sandbox') ? ' selected="selected"' : '') . '>' . $this->l('Yes') . '</option>
                                        <option value="secure"' . (($this->payu_environment == 'secure') ? ' selected="selected"' : '') . '>' . $this->l('No') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="self-return">' . $this->l('Self-Return Enabled') . '</label></td>
                                <td>
                                    <select id="self-return" name="self-return">
                                        <option value="1"' . (($this->payu_self_return == 1) ? ' selected="selected"' : '') . '>' . $this->l('Yes') . '</option>
                                        <option value="0"' . (($this->payu_self_return == 0) ? ' selected="selected"' : '') . '>' . $this->l('No') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="validity_time">' . $this->l('Order Validity Time') . '&nbsp;&nbsp;</label></td>
                                <td>
                                    <select id="validity_time" name="validity_time">
                                        ' . $this->selectValidityTime($this->payu_validity_time) . '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="ship_abroad">' . $this->l('Ship Abroad') . '</label></td>
                                <td>
                                    <select id="ship_abroad" name="ship_abroad">
                                        <option value="true"' . (($this->payu_ship_abroad == true) ? ' selected="selected"' : '') . '>' . $this->l('Enable') . '</option>
                                        <option value="false"' . (($this->payu_ship_abroad == false) ? ' selected="selected"' : '') . '>' . $this->l('Disable') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="one_step">' . $this->l('OneStepCheckout Enabled') . '</label></td>
                                <td>
                                    <select id="one_step" name="one_step">
                                        <option value="1"' . (($this->payu_one_step_checkout == 1) ? ' selected="selected"' : '') . '>' . $this->l('Enabled') . '</option>
                                        <option value="0"' . (($this->payu_one_step_checkout == 0) ? ' selected="selected"' : '') . '>' . $this->l('Disabled') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <br class="clear"/>
                                    <input class="button" type="submit" name="submitPayU" value="' . $this->l('Save') . '" />
                                </td>
                            </tr>
                        </table>
					</fieldset>
                    <br class="clear"/>
					<fieldset>
					    <legend>' . $this->l('Payment statuses') . '</legend>
                        <table>
                            <tr>
                                <td valign="top"><label for="payu_status_pending">' . $this->l('Pending status') . '</label></td>
                                <td>
                                <select name="payu_status_pending" id="payu_status_pending">
                    ';

        foreach ($statuses_array as $id => $name)
            $output .= '<option value="' . $id . '" ' . ($id == $this->payu_status_pending ? 'selected="selected"' : '') . '>' . $name . '</option>';

        $output .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="payu_status_sent">' . $this->l('Sent status') . '</label></td>
                                <td>
                                <select name="payu_status_sent" id="payu_status_sent">
                    ';

        foreach ($statuses_array as $id => $name)
            $output .= '<option value="' . $id . '" ' . ($id == $this->payu_status_sent ? 'selected="selected"' : '') . '>' . $name . '</option>';

        $output .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="payu_status_complete">' . $this->l('Complete status') . '</label></td>
                                <td>
                                <select name="payu_status_complete" id="payu_status_complete">
                    ';

        foreach ($statuses_array as $id => $name)
            $output .= '<option value="' . $id . '" ' . ($id == $this->payu_status_complete ? 'selected="selected"' : '') . '>' . $name . '</option>';

        $output .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="payu_status_cancel">' . $this->l('Cancel status') . '</label></td>
                                <td>
                                <select name="payu_status_cancel" id="payu_status_cancel">
                    ';

        foreach ($statuses_array as $id => $name)
            $output .= '<option value="' . $id . '" ' . ($id == $this->payu_status_cancel ? 'selected="selected"' : '') . '>' . $name . '</option>';

        $output .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="payu_status_reject">' . $this->l('Reject status') . '</label></td>
                                <td>
                                <select name="payu_status_reject" id="payu_status_reject">
                    ';

        foreach ($statuses_array as $id => $name)
            $output .= '<option value="' . $id . '" ' . ($id == $this->payu_status_reject ? 'selected="selected"' : '') . '>' . $name . '</option>';

        $output .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <br class="clear"/>
                                    <input class="button" type="submit" name="submitPayU" value="' . $this->l('Save') . '" />
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <br class="clear"/>
					<fieldset>
					    <legend>' . $this->l('Parameters of test environment (Sandbox)') . '</legend>
                        <table>
                            <tr>
                                <td valign="top"><label for="oauth_client_name_sandbox">' . $this->l('POS ID') . '</label></td>
                                <td>
                                    <input size="120" id="oauth_client_name_sandbox" type="text" name="oauth_client_name_sandbox" value="' . $this->payu_oauth_client_name_sandbox . '" />
                                    <br /><em>' . $this->l('OAuth protocol - client_id') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="oauth_client_secret_sandbox">' . $this->l('Key (MD5)') . '</label></td>
                                <td>
                                    <input size="120" id="oauth_client_secret_sandbox" type="text" name="oauth_client_secret_sandbox" value="' . $this->payu_oauth_client_secret_sandbox . '" />
                                    <br /><em>' . $this->l('OAuth protocol - client_secret') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="signature_key_sandbox">' . $this->l('Second key (MD5)') . '</label></td>
                                <td>
                                    <input size="120" id="signature_key_sandbox" type="text" name="signature_key_sandbox" value="' . $this->payu_signature_key_sandbox . '" />
                                    <br /><em>' . $this->l('Symmetrical key for encrypting communication') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="pos_auth_key_sandbox">' . $this->l('Pos Auth Key') . '</label></td>
                                <td>
                                    <input size="120" id="pos_auth_key_sandbox" type="text" name="pos_auth_key_sandbox" value="' . $this->payu_pos_auth_key_sandbox . '" />
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <br class="clear"/>
                                    <input class="button" type="submit" name="submitPayU" value="' . $this->l('Save') . '" />
                                </td>
                            </tr>
                        </table>
					</fieldset>
					<br class="clear"/>
                    <fieldset>
					    <legend>' . $this->l('Parameters of production environment') . '</legend>
                        <table>
                            <tr>
                                <td valign="top"><label for="oauth_client_name">' . $this->l('ID POS') . '</label></td>
                                <td>
                                    <input size="120" id="oauth_client_name" type="text" name="oauth_client_name" value="' . $this->payu_oauth_client_name . '" />
                                    <br /><em>' . $this->l('OAuth protocol - client_id') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="oauth_client_secret">' . $this->l('Key (MD5)') . '</label></td>
                                <td>
                                    <input size="120" id="oauth_client_secret" type="text" name="oauth_client_secret" value="' . $this->payu_oauth_client_secret . '" />
                                    <br /><em>' . $this->l('OAuth protocol - client_secret') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="signature_key">' . $this->l('Second key (MD5)') . '</label></td>
                                <td>
                                    <input size="120" id="signature_key" type="text" name="signature_key" value="' . $this->payu_signature_key . '" />
                                    <br /><em>' . $this->l('Symmetrical key for encrypting communication') . '</em>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top"><label for="pos_auth_key">' . $this->l('Pos Auth Key') . '</label></td>
                                <td>
                                    <input size="120" id="pos_auth_key" type="text" name="pos_auth_key" value="' . $this->payu_pos_auth_key . '" />
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <br class="clear"/>
                                    <input class="button" type="submit" name="submitPayU" value="' . $this->l('Save') . '" />
                                </td>
                            </tr>
                        </table>
					</fieldset>
					<br class="clear"/>
					';


        $output .= '<fieldset><legend>' . $this->l('Settings of external resources') . '</legend>';
        $output .= '<label for="payment_button">' . $this->l('Payment button') . '&nbsp;&nbsp;</label>';
        $output .= '<select id="payment_button" name="payment_button">
						' . $this->selectButtonGraphics($this->payu_button, $media) . '
					</select>
					<div class="img-preview"><img src="" alt="payment_button" /></div>
					<br /><br class="clear"/>';
        $output .= '<label for="payment_logo">' . $this->l('Payment logo') . '&nbsp;&nbsp;</label>';
        $output .= '<select id="payment_logo" name="payment_logo">
						' . $this->selectLogoGraphics($this->payu_logo, $media) . '
					</select>
					<div class="img-preview"><img src="" alt="payment_logo" /></div>
					<br /><br class="clear"/>';
        $output .= '<label for="payment_advert">' . $this->l('Payment adverts') . '&nbsp;&nbsp;</label>';
        $output .= '<select id="payment_advert" name="payment_advert">
						' . $this->selectAdvertsGraphics($this->payu_img_advert, $media) . '
					</select>
					<div class="img-preview"><img src="" alt="payment_advert" /></div>
					<br /><br class="clear"/>';
        $output .= '<label for="payment_accept">' . $this->l('Payment accept') . '&nbsp;&nbsp;</label>';
        $output .= '<select id="payment_accept" name="payment_accept">
						' . $this->selectAcceptGraphics($this->payu_img_accept) . '
					</select>
					<div class="img-preview"><img src="" alt="payment_accept" /></div>
					<br /><br class="clear"/>';
        $output .= '<input class="button" type="submit" name="submitPayU" value="' . $this->l('Save') . '" style="margin-left: 200px;"/>
					</fieldset><br class="clear"/>';

        $output .= '<fieldset><legend>' . $this->l('PayU Plugin Information') . '</legend>
					<label>' . $this->l('Current version') . ':</label><input type="text" size="120" readonly value="' . $this->version . '" />
					<br /><br class="clear"/>
					<label>' . $this->l('Latest version') . ':</label><input type="text" size="120" readonly value="' . $upgrade['prestashop']['1.4.4']['version'] . '" />
					<br /><br class="clear"/>
					<label>' . $this->l('GitHub') . ':</label><input type="text" size="120" readonly value="' . $upgrade['prestashop']['1.4.4']['repository'] . '" />
					<br /><br class="clear"/>
					<label>' . $this->l('About plugin') . ':</label><textarea style="width: 628px; height: 48px;" readonly>' . $upgradeInfo['description'] . '</textarea>
					<br /><br class="clear"/>';
        $output .= '<label>&nbsp;</label>
					<a class="button" href="' . $upgradeInfo['docs']['guides'][0]['url'] . '" target="_blank">' . $upgradeInfo['docs']['guides'][0]['name'] . '</a> &nbsp;
					<a class="button" href="' . $upgradeInfo['docs']['guides'][1]['url'] . '" target="_blank">' . $upgradeInfo['docs']['guides'][1]['name'] . '</a> &nbsp;
					<a class="button" href="' . $upgradeInfo['docs']['website'][0]['url'] . '" target="_blank">' . $upgradeInfo['docs']['website'][0]['name'] . '</a>
					</fieldset>
					</form><br class="clear"/>';

        $output .= "<script type=\"text/javascript\">
						$(document).ready(function(){
							src1 = $('select#payment_button').val();
							$('img[alt=\"payment_button\"]').attr('src',src1);
							src2 = $('select#payment_logo').val();
							$('img[alt=\"payment_logo\"]').attr('src',src2);
							src3 = $('select#payment_advert').val();
							$('img[alt=\"payment_advert\"]').attr('src',src3);
							src4 = $('select#payment_accept').val();
							$('img[alt=\"payment_accept\"]').attr('src',src4);
							$('select#payment_button').live('change',function(){
								src1 = $('select#payment_button').val();
								$('img[alt=\"payment_button\"]').attr('src',src1);
							});
							$('select#payment_logo').live('change',function(){
								src2 = $('select#payment_logo').val();
								$('img[alt=\"payment_logo\"]').attr('src',src2);
							});
							$('select#payment_advert').live('change',function(){
								src3 = $('select#payment_advert').val();
								$('img[alt=\"payment_advert\"]').attr('src',src3);
							});
							$('select#payment_accept').live('change',function(){
								src4 = $('select#payment_accept').val();
								$('img[alt=\"payment_accept\"]').attr('src',src4);
							});
						});
					</script>
					<style type=\"text/css\">
						div.img-preview { margin:10px 0 0 210px; }
						img[alt=\"payment_advert\"] { max-width: 650px; }
					</style>
					";

        return $output;
    }

    /**
     * Cancel order handling
     */
    public function execCancelOrder($id_cart)
    {
        Tools::redirectLink('order.php');
    }

    /**
     * Notify order handling
     */
    public function execNotifyOrder($request)
    {
        if (isset($_GET['error'])) {
            Tools::redirectLink($this->myUrl . 'payment_error.php?error=' . Tools::getValue('error'));
        } else {
            if (!empty($request)) {
                $this->orderNotifyRequest($request);
            }
        }
    }

    /**
     * Shipping handling
     */
    public function execShipping($request)
    {
        header("Content-type: text/xml");
        echo $this->shippingCostRetrieveRequest($request);
    }

    /**
     * Payment checkout handling
     */
    public function execPaymentCheckout($cart)
    {
        return $this->execPayment($cart);
    }

    /**
     * Validation handling
     */
    public function execValidation()
    {
        global $smarty, $_SESSION;

        $result = $this->beforeSummary();

        if (!empty($result)) {
            $smarty->assign(array('message' => $this->l('An error occurred while verifying the order.')));
            return $this->fetchTemplate('/views/templates/front/', 'error');
        }
    }

    /**
     * Error handling
     */
    public function execError($code)
    {
        global $smarty;

        if (isset($code)) {
            $smarty->assign(array('message' => $this->l('An error occurred during payment. Error Code:') . ' ' . $code));
            return $this->fetchTemplate('/views/templates/front/', 'error');
        }
    }

    /**
     * Hook display on admin order details
     *
     * @param array $params Parameters
     * @return string html
     */
    public function hookAdminOrder($params)
    {
        $orderId = Tools::getValue('id_order');
        $sessionId = payu_session::existsByOrderId($orderId);
        $sid = new payu_session($sessionId);

        if (Tools::isSubmit('save-payu-status')) {
            $orderStatus = Tools::getValue('change-payu');
            if ($this->orderStatusUpdateRequest($sid->sid, $orderStatus, $orderId)) {
                $sid->status = $orderStatus;
                $sid->update();
            }
        }

        $status = ucfirst(strtolower(str_replace('_', ' ', $sid->status)));
        $this->_html .= '<form method="post" action="' . htmlentities($_SERVER['REQUEST_URI']) . '">
						<fieldset style="width: 400px; margin-top: 15px;">
						<legend>
						<img src="../modules/payu/icon.gif">
						PayU
						</legend>
						' . $this->l('Status') . ': ' . $this->l($status) . '<br /><br />
						' . $this->l('Change status') . ': <select name="change-payu" id="change-payu">
							' . $this->selectStatus($sid->status) . '
						</select>
						<input type="submit" class="button" name="save-payu-status" value="' . $this->l('Change') . '" />
						</fieldset>
						</form>';

        return $this->_html;
    }

    /**
     * Hook display on select payment
     *
     * @param array $params Parameters
     * @return string Content
     */
    public function hookPayment($params)
    {
        global $smarty;

        $img = $this->getImageSource(Configuration::get('PAYU_LOGO'));

        $smarty->assign(array(
            'image' => $img,
            'actionUrl' => $this->myUrl . 'payment.php'
        ));

        return $this->fetchTemplate('/views/templates/front/', 'payment');

    }

    /**
     * Hook display on payment return
     *
     * @param array $params Parameters
     * @return string Content
     */
    public function paymentReturn()
    {
        global $smarty;

        $errorval = intval(Tools::getValue('error', 0));

        if ($errorval != 0) {
            $smarty->assign(array(
                'errormessage' => ''
            ));
        }

        return $this->fetchTemplate('/views/templates/front/', 'payment_return');
    }

    /**
     * Hook display on shopping cart summary
     *
     * @param array $params Parameters
     * @return string Content
     */
    public function hookShoppingCart($params)
    {
        global $smarty;

        if (Configuration::get('PAYU_ONE_STEP_CHECKOUT')) {

            $img = $this->getImageSource(Configuration::get('PAYU_BUTTON'));

            if (Validate::isLoadedObject($params['cart'])) {
                $smarty->assign(array(
                    'image' => $img,
                    'checkout_url' => $this->myUrl . 'payment_checkout.php',
                    'express_enabled' => empty($params['cart']->id_customer)
                ));
            }

            return $this->fetchTemplate('/views/templates/front/express_checkout/', 'express_checkout');
        }
    }

    public function hookShoppingCartExtra($params)
    {
        return $this->hookShoppingCart($params);
    }

    /**
     * Hook display on right column
     *
     * @param array $params Parameters
     * @return string Content
     */
    public function hookRightColumn($params)
    {
        global $smarty;

        $img = $this->getImageSource(Configuration::get('PAYU_IMG_ADVERT'));

        $smarty->assign(array(
            'image' => $img,
            'url' => $this->info_url,
        ));

        return $this->fetchTemplate('/views/templates/front/', 'column');
    }

    /**
     * Hook display on left column
     *
     * @param array $params Parameters
     * @return string Content
     */
    public function hookLeftColumn($params)
    {
        return $this->hookRightColumn($params);
    }

    /**
     * Session Handler
     *
     * @param $sessionId,$orderId,$payUOrderStatus,$cartId
     */
    public function saveSID($sessionId, $orderId, $payUOrderStatus, $cartId = 0)
    {
        $sid = payu_session::existsBySID($sessionId);

        if ($sid > 0) {
            $sid = new payu_session($sid);
            //if($sid->status != $payUOrderStatus){
            $sid->status = $payUOrderStatus;
            $sid->id_order = $orderId;
            $sid->id_cart = $cartId;
            //$sid->create_at = date('Y-m-d H:i:s');
            $sid->update_at = date('Y-m-d H:i:s');
            $sid->update();
            //}
        } else {
            $sid = new payu_session();
            $sid->id_order = $orderId;
            $sid->id_cart = $cartId;
            $sid->sid = $sessionId;
            $sid->status = $payUOrderStatus;
            $sid->create_at = date('Y-m-d H:i:s');
            $sid->update_at = date('Y-m-d H:i:s');
            $sid->add();
        }

    }

    /* Update order status */
    private function updateOrderStatus($response)
    {
        $status = $response[0];
        $paymentStatus = $response[1];

        $orderId = intval($response[2]);
        $history = new OrderHistory();
        $history->id_order = $orderId;

        if (_PS_VERSION_ < '1.5') {
            $orderState = OrderHistory::getLastOrderState($orderId);
            $orderStateId = $orderState->id;
        } else {
            $order = new Order($orderId);
            $orderStateId = $order->current_state;
        }

        if (in_array($orderStateId, array($this->payu_status_pending, $this->payu_status_sent)) && $paymentStatus == PayU::PAYMENT_STATUS_END && $status == PayU::ORDER_STATUS_COMPLETE) {
            if ($orderStateId != $this->payu_status_complete) {
                $history->changeIdOrderState($this->payu_status_complete, $orderId, false);
                $history->addWithemail(true);
            }
        } elseif ($orderStateId == $this->payu_status_pending && $status == PayU::ORDER_STATUS_PENDING && $paymentStatus == PayU::PAYMENT_STATUS_SENT) {
            if ($orderStateId != $this->payu_status_sent) {
                $history->changeIdOrderState($this->payu_status_sent, $orderId, false);
                $history->addWithemail(true);
            }
        } else {
            switch ($status) {
                case PayU::ORDER_STATUS_COMPLETE :
                    if ($orderStateId != $this->payu_status_complete) {
                        $history->changeIdOrderState($this->payu_status_complete, $orderId);
                        $history->addWithemail(true);
                    }
                    break;
                case PayU::ORDER_STATUS_CANCEL :
                    if ($orderStateId != $this->payu_status_cancel) {
                        $history->changeIdOrderState($this->payu_status_cancel, $orderId);
                        $history->addWithemail(true);
                    }
                    break;
                case PayU::ORDER_STATUS_REJECT :
                    if ($orderStateId != $this->payu_status_reject) {
                        $history->changeIdOrderState($this->payu_status_reject, $orderId);
                        $history->addWithemail(true);
                    }
                    break;
                case PayU::ORDER_STATUS_SENT :
                    if ($orderStateId != $this->payu_status_sent) {
                        $history->changeIdOrderState($this->payu_status_sent, $orderId);
                        $history->addWithemail(false);
                    }
                    break;
            }
        }
    }

    /* Update customer data status */
    public function updateCustomerData($cartId)
    {
        $cart = new Cart($cartId);

        $customerId = $cart->id_customer;
        $invoiceAddressId = $cart->id_address_invoice;
        $deliveryAddressId = $cart->id_address_delivery;

        $ips = payu_session::existsByCartId($cartId);
        $payuSession = new payu_session($ips);

        $result = OpenPayU_Order::retrieve($payuSession->sid);
        $response = $result->getResponse();

        $orderRetrieveResponse = $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse'];

        $this->updateOrderData($payuSession->id_order, $orderRetrieveResponse);
    }

    /* Update order data (clear address and customer guest) */
    private function updateOrderData($orderId, $orderRetrieveResponse)
    {
        $order = new Order($orderId);

        $customerRecord = isset($orderRetrieveResponse['CustomerRecord']) ? $orderRetrieveResponse['CustomerRecord'] : array();
        $shipping = isset($orderRetrieveResponse['Shipping']) ? $orderRetrieveResponse['Shipping'] : array();
        $invoice = isset($orderRetrieveResponse['Invoice']) ? $orderRetrieveResponse['Invoice'] : array();

        $customerId = $order->id_customer;

        if (!empty($shipping)) {

            // Update order_carrier
            if (isset($shipping['ShippingType'])) {
                preg_match_all("'([0-9]+)'si", trim($shipping['ShippingType'], ')'), $carrier);
                $carrierId = ($carrier[0][count($carrier[0]) - 1]);

                if (!empty($carrierId)) {
                    $order->id_carrier = $carrierId;

                    $id_order_carrier = Db::getInstance()->getValue('
                        SELECT `id_order_carrier`
                        FROM `' . _DB_PREFIX_ . 'order_carrier`
                        WHERE `id_order` = ' . (int)$orderId . '
                        AND (`id_order_invoice` IS NULL OR `id_order_invoice` = 0)');

                    if ($id_order_carrier) {
                        $shipping_cost_tax_excl = $this->toDecimal(intval($shipping['ShippingCost']['Net']));
                        $shipping_cost_tax_incl = $this->toDecimal(intval($shipping['ShippingCost']['Gross']));

                        $order_carrier = new OrderCarrier($id_order_carrier);
                        $order_carrier->id_carrier = (int)$order->id_carrier;
                        $order_carrier->shipping_cost_tax_excl = $shipping_cost_tax_excl;
                        $order_carrier->shipping_cost_tax_incl = $shipping_cost_tax_incl;
                        $order_carrier->update();

                        $order->total_shipping = $order_carrier->shipping_cost_tax_incl;
                        $order->total_shipping_tax_incl = $order_carrier->shipping_cost_tax_incl;
                        $order->total_shipping_tax_excl = $order_carrier->shipping_cost_tax_excl;

                        if ((isset($orderRetrieveResponse['PaidAmount']) && $orderRetrieveResponse['OrderStatus'] == 'ORDER_STATUS_COMPLETE' && $orderRetrieveResponse['PaymentStatus'] == 'PAYMENT_STATUS_END') && intval($order->total_paid_real) == 0) {
                            $order->total_paid = $order->total_products_wt + $order->total_shipping_tax_incl;
                            $order->total_paid_tax_incl = $order->total_paid;
                            $order->total_paid_tax_excl = $order->total_products + $order->total_shipping_tax_excl;

                            if (_PS_VERSION_ < '1.5') {
                                $this->total_paid_real = $this->toDecimal(intval($orderRetrieveResponse['PaidAmount']));
                            } else {
                                $order->addOrderPayment($this->toDecimal(intval($orderRetrieveResponse['PaidAmount'])), $this->displayName, $orderRetrieveResponse['SessionId']);
                            }
                        }
                    }
                }
            }

            $countryId = intval(Country::getByIso($shipping['Address']['CountryCode']) ? Country::getByIso($shipping['Address']['CountryCode']) : Configuration::get('PS_COUNTRY_DEFAULT'));
            $shippingRecipientName = explode(' ', $shipping['Address']['RecipientName']);

            $deliveryAddress = new Address();
            $deliveryAddress->id_customer = $customerId;
            $deliveryAddress->id_country = $countryId;
            $deliveryAddress->id_state = 0;
            $deliveryAddress->alias = 'PayU_delivery_(' . $orderId . ')_' . time();
            $deliveryAddress->firstname = $shippingRecipientName[0];
            $deliveryAddress->lastname = $shippingRecipientName[1];
            $deliveryAddress->address1 = $shipping['Address']['Street'] . ' ' . $shipping['Address']['HouseNumber'] . (isset($shipping['Address']['ApartmentNumber']) ? '/' . $shipping['Address']['ApartmentNumber'] : '');
            $deliveryAddress->postcode = $shipping['Address']['PostalCode'];
            $deliveryAddress->city = $shipping['Address']['City'];
            $deliveryAddress->deleted = 0;
            $deliveryAddress->add();

            $order->id_address_delivery = $deliveryAddress->id;
        }


        if (!empty($invoice)) {
            $invoiceCountryId = (Country::getByIso($invoice['Billing']['CountryCode'])) ? Country::getByIso($invoice['Billing']['CountryCode']) : Configuration::get('PS_COUNTRY_DEFAULT');
            $invoiceRecipientName = explode(' ', $invoice['Billing']['RecipientName']);

            $invoiceAddress = new Address();
            $invoiceAddress->id_customer = $order->id_customer;
            $invoiceAddress->id_country = $invoiceCountryId;
            $invoiceAddress->id_state = 0;
            $invoiceAddress->alias = 'PayU_invoice_(' . $orderId . ')_' . time();
            $invoiceAddress->firstname = $invoiceRecipientName[0];
            $invoiceAddress->lastname = $invoiceRecipientName[1];
            $invoiceAddress->address1 = $invoice['Billing']['Street'] . ' ' . $invoice['Billing']['HouseNumber'] . (isset($invoice['Billing']['ApartmentNumber']) ? '/' . $invoice['Billing']['ApartmentNumber'] : '');
            $invoiceAddress->postcode = $invoice['Billing']['PostalCode'];
            $invoiceAddress->city = $invoice['Billing']['City'];
            $invoiceAddress->phone = $invoice['Billing']['RecipientPhone'];
            $invoiceAddress->vat_number = $invoice['Billing']['TIN'];
            $invoiceAddress->deleted = 0;
            $invoiceAddress->add();

            $order->id_address_invoice = $invoiceAddress->id;
        }

        $customer = new Customer($order->id_customer);
        if ($customer->isGuest()) {
            $customer->deleted = 1;
            $customer->update();
        }

        $order->update();
    }

    /**
     * Initialize PayU connection configuration
     */
    private function initializeOpenPayUConfiguration()
    {
        $this->loadConfiguration();

        $environment = $this->payu_environment;

        if ($environment == 'sandbox') {
            OpenPayU_Configuration::setEnvironment('sandbox');
            OpenPayU_Configuration::setMerchantPosId($this->payu_pos_id_sandbox);
            OpenPayU_Configuration::setPosAuthKey($this->payu_pos_auth_key_sandbox);
            OpenPayU_Configuration::setClientId($this->payu_oauth_client_name_sandbox);
            OpenPayU_Configuration::setClientSecret($this->payu_oauth_client_secret_sandbox);
            OpenPayU_Configuration::setSignatureKey($this->payu_signature_key_sandbox);
        } else {
            OpenPayU_Configuration::setEnvironment('secure');
            OpenPayU_Configuration::setMerchantPosId($this->payu_pos_id);
            OpenPayU_Configuration::setPosAuthKey($this->payu_pos_auth_key);
            OpenPayU_Configuration::setClientId($this->payu_oauth_client_name);
            OpenPayU_Configuration::setClientSecret($this->payu_oauth_client_secret);
            OpenPayU_Configuration::setSignatureKey($this->payu_signature_key);
        }
    }

    /**
     * Initializes the payment
     *
     * @param $cart,$carriers,$isoLang
     * @return array
     */
    public function orderCreateRequest($cart, $carriers)
    {
        global $link;

        $ret = array();

        $_SESSION['sessionId'] = $cart->id . '-' . md5(rand() . rand() . rand() . rand());

        $shippingCost = array();
        $items = array();
        $shoppingCart = array();
        $order = array();
        $OCReq = array();
        $total = 0;

        $currency = Currency::getCurrency($cart->id_currency);
        $countryCode = Tools::strtoupper(Configuration::get('PS_LOCALE_COUNTRY'));

        $country = new Country(Country::getByIso($countryCode));

        $cartProducts = $cart->getProducts();

        if ($cart->isVirtualCart()) {
            $orderType = 'VIRTUAL';
        } else {
            $orderType = 'MATERIAL';
        }

        foreach ($cartProducts as $product) {
            $tax = explode('.', $product['rate']);
            $price_wt = $this->toAmount($product['price_wt']);
            $price = $this->toAmount($product['price']);
            $total += $this->toAmount($product['total_wt']);

            $item = array(
                'Quantity' => (int)$product['quantity'],
                'Product' => array(
                    'Name' => $product['name'],
                    'UnitPrice' => array(
                        'Gross' => $price_wt,
                        'Net' => $price,
                        'Tax' => ($price_wt - $price)
                    )
                )
            );

            if (!empty($tax[0]))
                $item['Product']['UnitPrice']['TaxRate'] = $tax[0];

            $item['Product']['UnitPrice']['CurrencyCode'] = $currency['iso_code'];

            $items[]['ShoppingCartItem'] = $item;
        }

        $carrierList = array();

        $tax_rate = 0;
        $tax_amount = 0;
        $free_shipping = false;

        # check is free shipping for cart
        if (_PS_VERSION_ >= '1.5') {
            foreach ($cart->getCartRules() as $rule)
                if ($rule['free_shipping']) {
                    $free_shipping = true;
                    break;
                }
        }

        if ($cart->id_carrier > 0) {
            $selectedCarrier = new Carrier($cart->id_carrier);
            $shippingMethod = $selectedCarrier->getShippingMethod();

            if ($free_shipping == false) {
                $price = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$cart->id_carrier, true, $country, $cartProducts));
                $price_tax_exc = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$cart->id_carrier, false, $country, $cartProducts));
                $tax_amount = intval(($price - $price_tax_exc) * 100);
            } else {
                $price = 0;
                $price_tax_exc = 0;
                $tax_amount = 0;
            }

            if (intval($selectedCarrier->active) == 1) {
                $carrierList[0]['ShippingCost'] = array(
                    'Type' => $selectedCarrier->name . ' (' . $selectedCarrier->id . ')',
                    'CountryCode' => $countryCode,
                    'Price' => array(
                        'Gross' => $this->toAmount($price),
                        'Net' => $this->toAmount($price_tax_exc),
                        'Tax' => $tax_amount
                    )
                );

                if (!empty($tax_rate))
                    $carrierList[0]['ShippingCost']['Price']['TaxRate'] = $tax_rate;

                $carrierList[0]['ShippingCost']['Price']['CurrencyCode'] = $currency['iso_code'];
            }
        } else {
            $i = 0;
            if ($carriers) {
                foreach ($carriers as $carrier) {
                    $c = new Carrier((int)$carrier['id_carrier']);

                    $shippingMethod = $c->getShippingMethod();

                    if ($free_shipping == false) {
                        $price = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier'], true, $country, $cartProducts));
                        $price_tax_exc = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier'], false, $country, $cartProducts));
                        $tax_amount = intval(($price - $price_tax_exc) * 100);
                    } else {
                        $price = 0;
                        $price_tax_exc = 0;
                        $tax_amount = 0;
                    }

                    if ($carrier['id_carrier'] != $cart->id_carrier) {
                        if (intval($carrier['active']) == 1) {
                            $carrierList[$i]['ShippingCost'] = array(
                                'Type' => $carrier['name'] . ' (' . $carrier['id_carrier'] . ')',
                                'CountryCode' => $countryCode,
                                'Price' => array(
                                    'Gross' => $this->toAmount($price),
                                    'Net' => $this->toAmount($price_tax_exc),
                                    'Tax' => $tax_amount
                                )
                            );

                            if (!empty($tax_rate))
                                $carrierList[$i]['ShippingCost']['Price']['TaxRate'] = $tax_rate;

                            $carrierList[$i]['ShippingCost']['Price']['CurrencyCode'] = $currency['iso_code'];

                            $i++;
                        }
                    }
                }
            }
        }

        $shippingCost = array(
            'CountryCode' => $countryCode,
            'ShipToOtherCountry' => $this->payu_ship_abroad,
            'ShippingCostList' => $carrierList
        );

        $shoppingCart = array(
            'GrandTotal' => ($this->toAmount($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)) < $total ? $total : $this->toAmount($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING))),
            'DiscountTotal' => ($this->toAmount($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)) < $total ? $total - $this->toAmount($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)) : 0),
            'CurrencyCode' => $currency['iso_code'],
            'ShoppingCartItems' => $items
        );

        $order = array(
            'MerchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'SessionId' => $_SESSION['sessionId'],
            'OrderUrl' => $link->getPageLink(__PS_BASE_URI__ . 'guest-tracking.php'),
            'OrderCreateDate' => date("c"),
            'ValidityTime' => $this->payu_validity_time,
            'InvoiceDisabled' => (Configuration::get('PS_INVOICE')) ? 'false' : 'true',
            'OrderDescription' => $this->l('Order for cart: ') . $cart->id . $this->l(' from the store: ') . Configuration::get('PS_SHOP_NAME'),
            'MerchantAuthorizationKey' => OpenPayU_Configuration::getPosAuthKey(),
            'OrderType' => $orderType,
            'ShoppingCart' => $shoppingCart
        );

        $OCReq = array(
            'ReqId' => md5(rand()),
            'CustomerIp' => (($_SERVER['REMOTE_ADDR'] == "::1" || $_SERVER['REMOTE_ADDR'] == "::" || !preg_match("/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m", $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR']),
            'NotifyUrl' => $this->myUrl . 'payment_notify.php?order=' . $cart->id,
            'OrderCancelUrl' => $this->myUrl . 'payment_cancel.php?order=' . $cart->id,
            'OrderCompleteUrl' => $this->myUrl . 'payment_succcess.php?order=' . $cart->id,
            'Order' => $order,
            'ShippingCost' => array(
                'AvailableShippingCost' => $shippingCost,
                'ShippingCostsUpdateUrl' => $this->myUrl . 'payment_shipping.php?order=' . $cart->id
            )
        );

        if (!empty($cart->id_customer)) {
            $customer = new Customer((int)$cart->id_customer);

            if ($customer->email) {
                $customer_sheet = array(
                    'Email' => $customer->email,
                    'FirstName' => $customer->firstname,
                    'LastName' => $customer->lastname
                );

                if (!empty($cart->id_address_delivery)) {
                    $address = new Address((int)$cart->id_address_delivery);
                    $country = new Country((int)$address->id_country);

                    if (!empty($address->phone))
                        $customer_sheet['Phone'] = $address->phone;

                    $customer_sheet['Shipping'] = array(
                        'Street' => $address->address1,
                        'PostalCode' => $address->postcode,
                        'City' => $address->city,
                        'CountryCode' => Tools::strtoupper($country->iso_code),
                        'AddressType' => 'SHIPPING',
                        'RecipientName' => trim($address->firstname . ' ' . $address->lastname),
                        'RecipientPhone' => $address->phone,
                        'RecipientEmail' => $customer->email
                    );
                }

                if (!empty($cart->id_address_invoice) && Configuration::get('PS_INVOICE')) {
                    $address = new Address((int)$cart->id_address_invoice);
                    $country = new Country((int)$address->id_country);

                    $customer_sheet['Invoice'] = array(
                        'Street' => $address->address1,
                        'PostalCode' => $address->postcode,
                        'City' => $address->city,
                        'CountryCode' => Tools::strtoupper($country->iso_code),
                        'AddressType' => 'BILLING',
                        'RecipientName' => trim($address->firstname . ' ' . $address->lastname),
                        'TIN' => $address->vat_number
                    );
                }

                $OCReq['Customer'] = $customer_sheet;
            }
        }

        $result = OpenPayU_Order::create($OCReq);

        if ($result->getSuccess()) {
            $result = OpenPayU_OAuth::accessTokenByClientCredentials();
            $ret = array(
                'summaryUrl' => OpenPayU_Configuration::getSummaryUrl(),
                'sessionId' => $_SESSION['sessionId'],
                'oauthToken' => $result->getAccessToken(),
                'langCode' => Tools::strtolower(Language::getIsoById($cart->id_lang))
            );

            $this->saveSID($_SESSION['sessionId'], 0, 'ORDER_STATUS_PENDING', $cart->id);
        } else {
            Logger::addLog(trim($result->getError() . ' ' . $result->getMessage() . ' ' . $_SESSION['sessionId']), 1, 0, 'PayU');
        }

        return $ret;
    }

    /**
     * Processing the BeforeSummary from PayU
     *
     * @return array
     */
    private function beforeSummary()
    {
        $ret = array();
        $result = OpenPayU_OAuth::accessTokenByCode(Tools::getValue('code'), $this->myUrl . 'validation.php');
        $cartId = $this->getCartIdBySessionId($_SESSION['sessionId']);
        if ($result->getSuccess()) {
            $userEmail = $result->getPayuUserEmail();
            if (!empty($userEmail)) {
                $customerId = Customer::customerExists($userEmail, true);
                if ($customerId > 0) {
                    $cart = new Cart($cartId);
                    $cart->id_customer = $customerId;
                    $cart->update();
                }
            }
            $this->saveSID($_SESSION['sessionId'], 0, 'ORDER_STATUS_PENDING', $cartId);
            ob_clean();
            Header("Location: " . OpenPayu_Configuration::getSummaryUrl() . "?sessionId=" . $_SESSION['sessionId'] . "&oauth_token=" . $result->getAccessToken());
        } else {
            Logger::addLog(trim($result->getError() . ' ' . $result->getMessage() . ' ' . $_SESSION['sessionId']), 1, 0, 'PayU');
        }

        return $ret;
    }

    /**
     * Processing the ShippingCostRetrieveRequest from PayU
     *
     * @param $request
     * @return array
     */
    private function shippingCostRetrieveRequest($request)
    {
        $xml = stripslashes($request);
        $result = OpenPayU_Order::consumeMessage($xml);
        if ($result->getMessage() == 'ShippingCostRetrieveRequest') {
            $sessionID = $result->getSessionId();
            $cartID = $this->getCartIdBySessionId($sessionID);
            $cart = new Cart($cartID);
            $iso_country = $result->getCountryCode();
            if ($id_country = Country::getByIso($iso_country)) {
                if ($id_zone = Country::getIdZone($id_country)) {
                    $c = new Carrier;
                    $zones = $c->getZones();
                    $carriers = Carrier::getCarriersForOrder($id_zone);
                    $currency = Currency::getCurrency($cart->id_currency);
                    if ($carriers) {
                        $carrierList = array();
                        foreach ($carriers as $carrier) {
                            $c = new Carrier((int)$carrier['id_carrier']);
                            $shippingMethod = $c->getShippingMethod();
                            $price = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier']));
                            $price_tax_exc = ($shippingMethod == Carrier::SHIPPING_METHOD_FREE ? 0 : $cart->getOrderShippingCost((int)$carrier['id_carrier'], false));
                            if ($carrier['id_carrier'] != $cart->id_carrier) {
                                $carrierList[]['ShippingCost'] = array(
                                    'Type' => $carrier['name'] . ' (' . $carrier['id_carrier'] . ')',
                                    'CountryCode' => Tools::strtoupper($iso_country),
                                    'Price' => array(
                                        'Gross' => $this->toAmount($price),
                                        'Net' => $this->toAmount($price_tax_exc),
                                        'Tax' => '23',
                                        'TaxRate' => '23',
                                        'CurrencyCode' => Tools::strtoupper($currency['iso_code'])
                                    )
                                );
                            }
                        }
                        $shippingCost = array(
                            'CountryCode' => Tools::strtoupper($iso_country),
                            'ShipToOtherCountry' => $this->payu_ship_abroad,
                            'ShippingCostList' => $carrierList
                        );
                        $xml = OpenPayU::buildShippingCostRetrieveResponse($shippingCost, $result->getReqId(), $iso_country);
                        return $xml;
                    } else {
                        Logger::addLog('carrier by id_zone is undefined');
                    }
                } else {
                    Logger::addLog('id_zone by id_country is undefined');
                }
            } else {
                Logger::addLog($iso_country . ' is undefined');
            }
        }

        return false;
    }

    /**
     * Processing the OrderNotifyRequest from PayU
     *
     * @param $request
     */
    private function orderNotifyRequest($request)
    {
        $result = OpenPayU_Order::consumeMessage($request);

        ob_start();
        if ($result->getMessage() == 'OrderNotifyRequest') {
            $sessionId = $result->getSessionId();

            if (!$sessionId)
                return false;

            $cartId = $this->getCartIdBySessionId($sessionId);

            if (!$cartId)
                return false;

            $orderId = intval($this->getOrderIdBySessionId($sessionId));

            if (!$orderId)
                return false;

            $result = OpenPayU_Order::retrieve($sessionId);
            $response = $result->getResponse();

            $orderRetrieveResponse = $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse'];
            $payUOrderStatus = $orderRetrieveResponse['OrderStatus'];
            $payUPaymentStatus = (isset($orderRetrieveResponse['PaymentStatus'])) ? $orderRetrieveResponse['PaymentStatus'] : false;

            $this->saveSID($sessionId, $orderId, $payUOrderStatus, $cartId);

            if ($payUOrderStatus == 'ORDER_STATUS_COMPLETE' && $payUPaymentStatus == 'PAYMENT_STATUS_END') {
                $this->updateOrderData($orderId, $orderRetrieveResponse);
            }

            $this->updateOrderStatus(array($payUOrderStatus, $payUPaymentStatus, $orderId));

        }
        ob_end_flush();
    }

    /**
     * Processing the updateStatus to PayU
     *
     * @param $sessionId,$status,$orderId
     */
    private function orderStatusUpdateRequest($sessionId, $status, $orderId)
    {

        ob_start();
        $result = OpenPayU_Order::updateStatus($sessionId, $status, false);
        if ($result->getSuccess()) {
            if ($status == 'ORDER_STATUS_COMPLETE') {
                $result = OpenPayU_Order::retrieve($sessionId);
                $response = $result->getResponse();
                $orderRetrieveResponse = $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse'];
                $this->updateOrderData($orderId, $orderRetrieveResponse);
            }
            $this->updateOrderStatus(array($status, 'PAYMENT_STATUS_END', $orderId));
            return $result;
            ob_end_flush();
        } else {
            Logger::addLog(trim($result->getError() . ' ' . $result->getMessage() . ' ' . $sessionId), 1, 0, 'PayU');
            return false;
        }

    }

    /**
     * Return PayU json data
     *
     * @param $lang
     * @return stdObject
     */
    private function mediaOpenPayU($lang = 'pl')
    {
        $url = 'http://openpayu.com/' . $lang . '/goods/json';
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 0);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($c);
        curl_close($c);
        $json = json_decode($content);

        return $json->media;
    }

    /**
     * Return PayU json data
     *
     * @param $lang
     * @return array
     */
    private function upgradeOpenPayU($lang = 'pl')
    {
        $url = 'http://openpayu.com/' . $lang . '/goods/json';
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 0);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($c);
        curl_close($c);
        $json = json_decode($content, true);

        return $json['plugins'];
    }

    /**
     * Return PayU json data
     *
     * @param $lang
     * @return array
     */
    private function upgradeOpenPayUInfo($url)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 0);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($c);
        curl_close($c);
        $json = json_decode($content, true);

        return $json;
    }

    public function getModuleAddress($http = false, $entities = false)
    {
        return self::getShopDomainAddress($http, $entities) . (__PS_BASE_URI__ . 'modules/' . $this->name . '/');
    }

    public static function getShopDomainAddress($http = false, $entities = false)
    {
        if (method_exists('Tools', 'getShopDomainSsl'))
            return Tools::getShopDomainSsl($http, $entities);
        else {
            if (!($domain = Configuration::get('PS_SHOP_DOMAIN_SSL')))
                $domain = self::getHttpHost();
            if ($entities)
                $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
            if ($http)
                $domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $domain;
            return $domain;
        }
    }

    private function getImageSource($img)
    {
        if (($this->lang == 'pl' && !preg_match('/' . $this->lang . '/', $img)) || $this->lang == 'pl') {
            return $img = str_replace('/en/', '/pl/', $img);
        } else {
            return $img = str_replace('/pl/', '/en/', $img);
        }
    }
}
