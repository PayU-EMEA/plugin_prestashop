<?php
/**
 * PayU module
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2016 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */


if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayuOauthCache/OauthCachePresta.php');


class PayU extends PaymentModule
{

    const CONDITION_PL = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf';
    const CONDITION_EN = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_en.pdf';
    const CONDITION_CS = 'http://static.payu.com/sites/terms/files/Podmínky pro provedení jednorázové platební transakce v PayU.pdf';

    public $cart = null;
    public $id_cart = null;
    public $order = null;
    public $payu_order_id = '';
    public $id_order = null;

    /** @var string */
    private $extOrderId = '';

    public function __construct()
    {
        $this->name = 'payu';
        $this->displayName = 'PayU';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.9';
        $this->author = 'PayU';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.6.0', 'max' => '1.7');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        parent::__construct();

        $this->displayName = $this->l('PayU');
        $this->description = $this->l('Accepts payments by PayU');

        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');

    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function install()
    {
        return (
            function_exists('curl_version') &&
            parent::install() &&
            in_array('curl', get_loaded_extensions()) &&
            $this->createInitialDbTable() &&
            $this->createHooks() &&
            Configuration::updateValue('PAYU_MC_POS_ID', '') &&
            Configuration::updateValue('PAYU_MC_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_ID', '') &&
            Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_SECRET', '') &&
            Configuration::updateValue('SANDBOX_PAYU_MC_POS_ID', '') &&
            Configuration::updateValue('SANDBOX_PAYU_MC_SIGNATURE_KEY', '') &&
            Configuration::updateValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID', '') &&
            Configuration::updateValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET', '') &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', $this->addNewOrderState('PAYU_PAYMENT_STATUS_PENDING',
                array('en' => 'PayU payment pending', 'pl' => 'Płatność PayU rozpoczęta', 'cs' => 'Transakce PayU je zahájena'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', $this->addNewOrderState('PAYU_PAYMENT_STATUS_SENT',
                array('en' => 'PayU payment waiting for confirmation', 'pl' => 'Płatność PayU oczekuje na odbiór', 'cs' => 'Transakce  čeká na přijetí'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', $this->addNewOrderState('PAYU_PAYMENT_STATUS_CANCELED',
                array('en' => 'PayU payment canceled', 'pl' => 'Płatność PayU anulowana', 'cs' => 'Transakce PayU zrušena'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', 2) &&
            Configuration::updateValue('PAYU_RETRIEVE', 1) &&
            Configuration::updateValue('PAYU_SANDBOX', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', '')
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('PAYU_POS_ID') ||
            !Configuration::deleteByName('PAYU_SIGNATURE_KEY') ||
            !Configuration::deleteByName('PAYU_OAUTH_CLIENT_ID') ||
            !Configuration::deleteByName('PAYU_OAUTH_CLIENT_SECRET') ||
            !Configuration::deleteByName('PAYU_MC_POS_ID') ||
            !Configuration::deleteByName('PAYU_MC_SIGNATURE_KEY') ||
            !Configuration::deleteByName('PAYU_MC_OAUTH_CLIENT_ID') ||
            !Configuration::deleteByName('PAYU_MC_OAUTH_CLIENT_SECRET') ||
            !Configuration::deleteByName('SANDBOX_PAYU_MC_POS_ID') ||
            !Configuration::deleteByName('SANDBOX_PAYU_MC_SIGNATURE_KEY') ||
            !Configuration::deleteByName('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID') ||
            !Configuration::deleteByName('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET') ||
            !Configuration::deleteByName('PAYU_RETRIEVE') ||
            !Configuration::deleteByName('PAYU_SANDBOX') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_ORDER')
        ) {
            return false;
        }
        return true;
    }


    public function initializeOpenPayU($currencyIsoCode)
    {
        $prefix = Configuration::get('PAYU_SANDBOX') ? 'SANDBOX_' : '';
        $payuPosId = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_POS_ID'));
        $payuSignatureKey = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_SIGNATURE_KEY'));
        $payuOauthClientId = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_OAUTH_CLIENT_ID'));
        $payuOauthClientSecret = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_OAUTH_CLIENT_SECRET'));

        if (!is_array($payuPosId) ||
            !is_array($payuSignatureKey) ||
            !$payuPosId[$currencyIsoCode] ||
            !$payuSignatureKey[$currencyIsoCode]
        ) {
            return false;
        }

        OpenPayU_Configuration::setEnvironment( Configuration::get('PAYU_SANDBOX') ? 'sandbox' : 'secure');
        OpenPayU_Configuration::setMerchantPosId($payuPosId[$currencyIsoCode]);
        OpenPayU_Configuration::setSignatureKey($payuSignatureKey[$currencyIsoCode]);
        if ($payuOauthClientId[$currencyIsoCode] && $payuOauthClientSecret[$currencyIsoCode]) {
            OpenPayU_Configuration::setOauthClientId($payuOauthClientId[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthClientSecret($payuOauthClientSecret[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthTokenCache(new OauthCachePresta());
        }
        OpenPayU_Configuration::setSender($this->getVersion());

        return true;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = '';
        $errors = array();

        if (Tools::isSubmit('submit' . $this->name)) {

            $PAYU_MC_POS_ID = array();
            $PAYU_MC_SIGNATURE_KEY = array();
            $PAYU_MC_OAUTH_CLIENT_ID = array();
            $PAYU_MC_OAUTH_CLIENT_SECRET = array();

            $SANDBOX_PAYU_MC_POS_ID = array();
            $SANDBOX_PAYU_MC_SIGNATURE_KEY = array();
            $SANDBOX_PAYU_MC_OAUTH_CLIENT_ID = array();
            $SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET = array();

            foreach (Currency::getCurrencies() as $currency) {
                $PAYU_MC_POS_ID[$currency['iso_code']] = Tools::getValue('PAYU_MC_POS_ID|'.$currency['iso_code']);
                $PAYU_MC_SIGNATURE_KEY[$currency['iso_code']] = Tools::getValue('PAYU_MC_SIGNATURE_KEY|'.$currency['iso_code']);
                $PAYU_MC_OAUTH_CLIENT_ID[$currency['iso_code']] = Tools::getValue('PAYU_MC_OAUTH_CLIENT_ID|'.$currency['iso_code']);
                $PAYU_MC_OAUTH_CLIENT_SECRET[$currency['iso_code']] = Tools::getValue('PAYU_MC_OAUTH_CLIENT_SECRET|'.$currency['iso_code']);
                $SANDBOX_PAYU_MC_POS_ID[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_POS_ID|'.$currency['iso_code']);
                $SANDBOX_PAYU_MC_SIGNATURE_KEY[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_SIGNATURE_KEY|'.$currency['iso_code']);
                $SANDBOX_PAYU_MC_OAUTH_CLIENT_ID[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID|'.$currency['iso_code']);
                $SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET|'.$currency['iso_code']);
            }

            if (
                !Configuration::updateValue('PAYU_MC_POS_ID', serialize($PAYU_MC_POS_ID)) ||
                !Configuration::updateValue('PAYU_MC_SIGNATURE_KEY', serialize($PAYU_MC_SIGNATURE_KEY)) ||
                !Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_ID', serialize($PAYU_MC_OAUTH_CLIENT_ID)) ||
                !Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_SECRET', serialize($PAYU_MC_OAUTH_CLIENT_SECRET)) ||
                !Configuration::updateValue('SANDBOX_PAYU_MC_POS_ID', serialize($SANDBOX_PAYU_MC_POS_ID)) ||
                !Configuration::updateValue('SANDBOX_PAYU_MC_SIGNATURE_KEY', serialize($SANDBOX_PAYU_MC_SIGNATURE_KEY)) ||
                !Configuration::updateValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID', serialize($SANDBOX_PAYU_MC_OAUTH_CLIENT_ID)) ||
                !Configuration::updateValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET', serialize($SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET)) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', (int)Tools::getValue('PAYU_PAYMENT_STATUS_PENDING')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', (int)Tools::getValue('PAYU_PAYMENT_STATUS_SENT')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_COMPLETED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_CANCELED')) ||
                !Configuration::updateValue('PAYU_RETRIEVE', (Tools::getValue('PAYU_RETRIEVE') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SANDBOX', (Tools::getValue('PAYU_SANDBOX') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', Tools::getValue('PAYU_PAYMENT_METHODS_ORDER'))
            ) {
                $errors[] = $this->l('Can not save configuration');
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }
            } else {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        $output .= $this->fetchTemplate('/views/templates/admin/info.tpl');
        return $output . $this->displayForm();
    }

    /**
     * @return mixed
     */
    public function displayForm()
    {

        $form['method'] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Integration method'),
                    'icon' => 'icon-th'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Display payment methods'),
                        'desc' => $this->l('Payment methods displayed on Prestashop checkout summary page'),
                        'name' => 'PAYU_RETRIEVE',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Payment Methods Order'),
                        'name' => 'PAYU_PAYMENT_METHODS_ORDER',
                        'desc' => $this->l('Enter payment methods values separated by comma. List of payment methods - http://developers.payu.com/pl/overview.html#paymethods'),
                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->l('SANDBOX mode'),
                        'name' => 'PAYU_SANDBOX',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );

        foreach (Currency::getCurrencies() as $currency) {
            $form['pos_' . $currency['iso_code']] = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('POS settings - currency: ') . $currency['name'] . ' (' . $currency['iso_code'] . ')',
                        'icon' => 'icon-cog'
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('POS ID'),
                            'name' => 'PAYU_MC_POS_ID|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Second key (MD5)'),
                            'name' => 'PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_id'),
                            'name' => 'PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_secret'),
                            'name' => 'PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    )
                )
            );
            $form['sandbox_pos_' . $currency['iso_code']] = array(
                'form' => array(
                    'legend' => array(
                        'title' => '<span style="color: red">' . $this->l('SANDBOX - ') . '</span>' . $this->l('POS settings - currency: ') . $currency['name'] . ' (' . $currency['iso_code'] . ')',
                        'icon' => 'icon-cog'
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('POS ID'),
                            'name' => 'SANDBOX_PAYU_MC_POS_ID|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Second key (MD5)'),
                            'name' => 'SANDBOX_PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_id'),
                            'name' => 'SANDBOX_PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_secret'),
                            'name' => 'SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    )
                )
            );
        }

        $form['statuses'] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Payment statuses'),
                    'icon' => 'icon-tag'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Pending status'),
                        'name' => 'PAYU_PAYMENT_STATUS_PENDING',
                        'options' => array(
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Waiting For Confirmation'),
                        'name' => 'PAYU_PAYMENT_STATUS_SENT',
                        'options' => array(
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Complete status'),
                        'name' => 'PAYU_PAYMENT_STATUS_COMPLETED',
                        'options' => array(
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Canceled status'),
                        'name' => 'PAYU_PAYMENT_STATUS_CANCELED',
                        'options' => array(
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->show_toolbar = false;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($form);
    }


    private function getConfigFieldsValues()
    {

        $config = array(
            'PAYU_PAYMENT_STATUS_PENDING' => Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            'PAYU_PAYMENT_STATUS_SENT' => Configuration::get('PAYU_PAYMENT_STATUS_SENT'),
            'PAYU_PAYMENT_STATUS_COMPLETED' => Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'),
            'PAYU_PAYMENT_STATUS_CANCELED' => Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'),
            'PAYU_RETRIEVE' => Configuration::get('PAYU_RETRIEVE'),
            'PAYU_SANDBOX' => Configuration::get('PAYU_SANDBOX'),
            'PAYU_PAYMENT_METHODS_ORDER' => Configuration::get('PAYU_PAYMENT_METHODS_ORDER')
        );

        foreach (Currency::getCurrencies() as $currency) {
            $config['PAYU_MC_POS_ID|' . $currency['iso_code']] = $this->ParseConfigByCurrency('PAYU_MC_POS_ID', $currency);
            $config['PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']] = $this->ParseConfigByCurrency('PAYU_MC_SIGNATURE_KEY', $currency);
            $config['PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']] = $this->ParseConfigByCurrency('PAYU_MC_OAUTH_CLIENT_ID', $currency);
            $config['PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']] = $this->ParseConfigByCurrency('PAYU_MC_OAUTH_CLIENT_SECRET', $currency);
            $config['SANDBOX_PAYU_MC_POS_ID|' . $currency['iso_code']] = $this->ParseConfigByCurrency('SANDBOX_PAYU_MC_POS_ID', $currency);
            $config['SANDBOX_PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']] = $this->ParseConfigByCurrency('SANDBOX_PAYU_MC_SIGNATURE_KEY', $currency);
            $config['SANDBOX_PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']] = $this->ParseConfigByCurrency('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID', $currency);
            $config['SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']] = $this->ParseConfigByCurrency('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET', $currency);
        }

        return $config;
    }

    private function ParseConfigByCurrency($key, $currency) {
        $data = Tools::unSerialize(Configuration::get($key));
        return is_array($data) && array_key_exists($currency['iso_code'], $data) ? $data[$currency['iso_code']] : '';
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function fetchTemplate($name)
    {
        return $this->display(__FILE__, $name);
    }

    /**
     * @return string
     */
    public function hookBackOfficeHeader()
    {
        $output = '<link type="text/css" rel="stylesheet" href="' . _MODULE_DIR_ . $this->name . '/css/payu.css" />';

        $vieworder = Tools::getValue('vieworder');
        $id_order = Tools::getValue('id_order');

        $refundable = false;

        if ($vieworder !== false && $id_order !== false) {
            $order = new Order($id_order);
            $order_payment = $this->getLastOrderPaymentByOrderId($id_order);

            $order_state_id = $order->current_state;

            if ($order->module = 'payu') {
                switch ($order_state_id) {
                    case Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'):
                        $refundable = true;
                        break;
                }
            }
        }

        $refund_type = Tools::getValue('payu_refund_type');
        $refund_amount = $refund_type === 'full' ? $order->total_paid : (float)Tools::getValue('payu_refund_amount');

        $this->context->smarty->assign('payu_refund_amount', $refund_amount);
        if (isset($order) && is_object($order)) {
            $this->context->smarty->assign('payu_refund_full_amount', $order->total_paid);
        }
        $this->context->smarty->assign('payu_refund_type', $refund_type);
        $this->context->smarty->assign('show_refund', $refundable);

        $refund_errors = array();

        if ($refundable && empty($refund_errors) && Tools::getValue('submitPayuRefund')) {

            if ($refund_amount > $order->total_paid) {
                $refund_errors[] = $this->l('The refund amount you entered is greater than paid amount.');
            }

            if (empty($refund_errors)) {

                $refund = $this->payuOrderRefund($refund_amount, $order_payment['id_session'], $id_order);

                if (!empty($refund)) {
                    if (!($refund[0] === true)) {
                        $refund_errors[] = $this->l('Refund error: ' . $refund[1]);
                    }
                } else {
                    $refund_errors[] = $this->l('Refund error...');
                }
                if (empty($refund_errors)) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$id_order;
                    $history->id_employee = (int)$this->context->employee->id;
                    $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), $id_order);
                    $history->addWithemail(true, array());

                    Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                }
            }
        }

        $this->context->smarty->assign('payu_refund_errors', $refund_errors);

        $template = $output . $this->fetchTemplate('/views/templates/admin/header16.tpl');
        return $template;
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS(($this->_path) . 'css/payu.css', 'all');
        $this->context->controller->addJS(($this->_path) . 'js/payu.js', 'all');
    }


    public function hookDisplayOrderDetail($params)
    {
        if ($this->hasRetryPayment($params['order']->id, $params['order']->current_state)) {
            $this->context->smarty->assign(
                array(
                    'payuImage' => $this->getPayuLogo(),
                    'payuActionUrl' => $this->context->link->getModuleLink(
                        'payu', 'payment', array('id_order' => $params['order']->id)
                    )
                )
            );

            if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
                $template = 'retryPayment.tpl';
            } else {
                $template = 'retryPayment17.tpl';
            }

            return $this->fetchTemplate($template);
        }
    }

    /**
     * Only for >=1.7
     * @param $params
     * @return array|void
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText($this->l('Payment by card or bank transfer via PayU'))
            ->setLogo($this->getPayuLogo('payu_u_icon.png'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'payment'));

        return array($paymentOption);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookPayment($params)
    {
        $link = $this->context->link->getModuleLink('payu', 'payment');

        $this->context->smarty->assign(array(
                'image' => $this->getPayuLogo(),
                'actionUrl' => $link)
        );

        $template = $this->fetchTemplate('/views/templates/hook/payment16.tpl');

        return $template;
    }

    public function hookDisplayPaymentEU()
    {
        $payment_options = array(
            'cta_text' => $this->l('Payment by card or bank transfer via PayU'),
            'logo' => $this->getPayuLogo(),
            'action' => $this->context->link->getModuleLink('payu', 'payment')
        );

        return $payment_options;
    }

    /**
     * @return null|string
     * @throws PrestaShopDatabaseException
     */
    public function hookAdminOrder($params)
    {

        $this->id_order = $params['id_order'];
        $output = '';

        $updateOrderStatusMessage = '';

        if (Tools::getValue('cancelPayuOrder')) {
            $this->payu_order_id = Tools::getValue('cancelPayuOrder');

            $updateOrderStatus = $this->sendPaymentUpdate(OpenPayuOrderStatus::STATUS_CANCELED);
            $updateOrderStatusMessage = $updateOrderStatus !== true ? $this->displayError($updateOrderStatus['message']) : $this->displayConfirmation($this->l('Update status request has been sent'));
        }

        $order_payment = $this->getLastOrderPaymentByOrderId($params['id_order']);

        $this->context->smarty->assign(array(
            'PAYU_ORDERS' => $this->getOrdersByOrderId($params['id_order']),
            'PAYU_ORDER_ID' => $this->id_order,
            'PAYU_CANCEL_ORDER_MESSAGE' => $updateOrderStatusMessage,
            'PAYU_PAYMENT_STATUS_OPTIONS' => '',
            'PAYU_PAYMENT_STATUS' => '',
            'PAYU_PAYMENT_ACCEPT' => false
        ));

        $isConfirmable = $order_payment['status'] == OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION
            || $order_payment['status'] == OpenPayuOrderStatus::STATUS_REJECTED;
        if ($isConfirmable) {

            $this->payu_order_id = $order_payment['id_session'];
            if (Tools::isSubmit('submitpayustatus') && $this->payu_order_id && trim(Tools::getValue('PAYU_PAYMENT_STATUS'))) {

                $updateOrderStatus = $this->sendPaymentUpdate(Tools::getValue('PAYU_PAYMENT_STATUS'));

                if ($updateOrderStatus === true) {
                    $output .= $this->displayConfirmation($this->l('Update status request has been sent'));
                } else {
                    $output .= $this->displayError($this->l('Update status request has not been completed correctly.' . ' ' . $updateOrderStatus['message']));
                }
            }

            $this->context->smarty->assign(array(
                'PAYU_PAYMENT_STATUS_OPTIONS' => $this->getPaymentAcceptanceStatusesList(),
                'PAYU_PAYMENT_STATUS' => $order_payment['status'],
                'PAYU_PAYMENT_ACCEPT' => $isConfirmable
            ));
        }

        return $output . $this->fetchTemplate('/views/templates/admin/status.tpl');
    }

    /**
     * @param int $order_id
     * @param int $order_state
     * @return bool
     */
    public function hasRetryPayment($order_id, $order_state)
    {
        $payuOrder = $this->getLastOrderPaymentByOrderId($order_id);

        if ((!$payuOrder && $order_state == (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING') ||
            ($payuOrder['status'] == OpenPayuOrderStatus::STATUS_CANCELED && $order_state == (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'))))
        {
            return true;
        }
        return false;
    }

    /**
     * @param null|string $payMethod
     * @return array
     * @throws Exception
     */
    public function orderCreateRequestByOrder($payMethod = null)
    {

        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        $currency = Currency::getCurrency($this->order->id_currency);

        if (!$this->initializeOpenPayU($currency['iso_code'])) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OPU not properly configured for currency: ' . $currency['iso_code']);
            Logger::addLog($this->displayName . ' ' . 'OPU not properly configured for currency: ' . $currency['iso_code'], 1);

            throw new \Exception('OPU not properly configured for currency: ' . $currency['iso_code']);
        }

        $ocreq = array(
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => $this->l('Order: ') . $this->order->id . ' - ' . $this->order->reference . ', ' . $this->l('Store: ') . Configuration::get('PS_SHOP_NAME'),
            'products' => array(
                array(
                    'quantity' => 1,
                    'name' => $this->l('Order: ') . $this->order->id . ' - ' . $this->order->reference,
                    'unitPrice' => $this->toAmount($this->order->total_paid)
                )
            ),
            'customerIp' => $this->getIP(),
            'notifyUrl' => $this->context->link->getModuleLink('payu', 'notification'),
            'continueUrl' => $this->context->link->getModuleLink('payu', 'success') . '?id=' . $this->extOrderId,
            'currencyCode' => $currency['iso_code'],
            'totalAmount' => $this->toAmount($this->order->total_paid),
            'extOrderId' => $this->extOrderId,
            'settings' => array(
                'invoiceDisabled' => true
            )
        );


        if ($this->getCustomer($this->order->id_customer)) {
            $ocreq['buyer'] = $this->getCustomer($this->order->id_customer);
        }

        if ($payMethod !== null) {
            $ocreq['payMethods'] = array(
                'payMethod' => array(
                    'type' => 'PBL',
                    'value' => $payMethod
                )
            );
        }

        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id, 'OrderCreateRequest: ');
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderCreateResponse: ');

            if ($result->getStatus() == 'SUCCESS') {
                return array(
                    'redirectUri' => urldecode($result->getResponse()->redirectUri),
                    'orderId' => $result->getResponse()->orderId
                );
            } else {
                SimplePayuLogger::addLog('order', __FUNCTION__, 'OpenPayU_Order::create($ocreq) NOT success!! ' . $this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage(), $this->payu_order_id));
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage()), 1);

                throw new \Exception($result->getError() . ' ' . $result->getMessage());
            }
        } catch (\Exception $e) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Exception catched! ' . $this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()));
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()), 1);

            throw new \Exception($e->getCode() . ' ' . $e->getMessage());
        }

    }

    public function updateOrderData($responseNotification = null)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Entrance: ', $this->payu_order_id);

        if (empty($this->payu_order_id)) {
            Logger::addLog($this->displayName . ' ' . 'Can not get order information - id_session is empty', 1);
        }

        $result = null;
        $this->configureOpuByIdOrder($this->id_order);

        if ($responseNotification) {
            $response = $responseNotification;
        } else {
            $raw = OpenPayU_Order::retrieve($this->payu_order_id);
            $response = $raw->getResponse();
        }

        SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderRetrieve response object: ');

        $payu_order = $responseNotification ? $response->order : $response->orders[0];

        if ($payu_order) {

            $this->order = new Order($this->id_order);
            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Order exists in PayU system ', $this->payu_order_id);
            $this->updateOrderState(isset($payu_order->status) ? $payu_order->status : null);
        }
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
     * @return string
     */
    public function getPayConditionUrl()
    {
        switch (Language::getIsoById($this->context->language->id)) {
            case 'pl':
                return self::CONDITION_PL;
                break;
            case 'cs':
                return self::CONDITION_CS;
                break;
            default:
                return self::CONDITION_EN;
        }
    }

    /**
     * @param array $currency
     * @return array
     */
    public function getPaymethods($currency)
    {
        try {
            $this->initializeOpenPayU($currency['iso_code']);

            $retreive = OpenPayU_Retrieve::payMethods(Language::getIsoById($this->context->language->id));
            if ($retreive->getStatus() == 'SUCCESS') {
                $response = $retreive->getResponse();

                return array(
                    'payByLinks' => $this->reorderPaymentMethods($response->payByLinks)
                );
            } else {
                return array(
                    'error' => $retreive->getStatus() . ': ' . OpenPayU_Util::statusDesc($retreive->getStatus())
                );
            }

        } catch (OpenPayU_Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }

    public function getPayuLogo($file = 'payu_logo.png')
    {
        return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/img/' . $file);
    }

    /**
     * @param string $id
     */
    public function generateExtOrderId($id)
    {
        $this->extOrderId = uniqid($id . '-', true);
    }

    /**
     * @return string
     */
    public function getExtOrderId()
    {
        return $this->extOrderId;
    }


    /**
     * @param string $status
     * @param int $idOrder
     * @param int $idCart
     * @param string $payuIdOrder
     * @param string $extOrderId
     * @return mixed
     */
    public function addOrderSessionId($status, $idOrder, $idCart, $payuIdOrder, $extOrderId)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'order_payu_payments (id_order, id_cart, id_session, ext_order_id, status, create_at)
				VALUES (' . (int)$idOrder . ', ' . (int)$idCart . ', "' . pSQL($payuIdOrder) . '", "' . pSQL($extOrderId) . '", "' . pSQL($status) . '", NOW())';

        SimplePayuLogger::addLog('order', __FUNCTION__, 'DB Insert ' . $sql, $payuIdOrder);

        if (Db::getInstance()->execute($sql)) {
            return (int)Db::getInstance()->Insert_ID();
        }

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

        return $result ? $result : false;
    }

    /**
     * @param $extOrderId
     * @return array | bool
     */
    public function getOrderPaymentByExtOrderId($extOrderId)
    {
        $result = Db::getInstance()->getRow('
			SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE ext_order_id = "' . pSQL($extOrderId) . '"
		');

        return $result ? $result : false;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function createInitialDbTable()
    {
        if (Db::getInstance()->ExecuteS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'order_payu_payments"')) {
            if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'order_payu_payments LIKE "ext_order_id"') == false) {
                return Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payu_payments ADD ext_order_id VARCHAR(64) NOT NULL AFTER id_session');
            }
            return true;
        } else {
            return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_payu_payments` (
					`id_payu_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`id_order` INT(10) UNSIGNED NOT NULL,
					`id_cart` INT(10) UNSIGNED NOT NULL,
					`id_session` varchar(64) NOT NULL,
					`ext_order_id` VARCHAR(64) NOT NULL,
					`status` varchar(64) NOT NULL,
					`create_at` datetime,
					`update_at` datetime
				)');
        }
    }

    /**
     * @param $id_order
     * @return bool | array
     */
    private function getLastOrderPaymentByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE id_order="' . addslashes($id_order) . '"
			ORDER BY create_at DESC';

        SimplePayuLogger::addLog('notification', __FUNCTION__, $sql, $this->payu_order_id);
        $result = Db::getInstance()->getRow($sql, false);

        return $result ? $result : false;
    }

    /**
     * @param $id_order
     * @return bool
     */
    private function hasLastPayuOrderIsCompleted($id_order)
    {
        $sql = 'SELECT status FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE id_order="' . addslashes($id_order) . '"
			ORDER BY create_at DESC';

        $result = Db::getInstance()->getRow($sql, false);

        return $result['status'] == OpenPayuOrderStatus::STATUS_COMPLETED;
    }

    /**
     * @param $id_order
     * @return bool | array
     * @throws PrestaShopDatabaseException
     */
    private function getOrdersByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE id_order="' . addslashes($id_order) . '"
			ORDER BY create_at DESC';

        SimplePayuLogger::addLog('notification', __FUNCTION__, $sql, $this->payu_order_id);
        $result = Db::getInstance()->executeS($sql, true, false);

        return $result ? $result : false;
    }

    /**
     * @param $status
     * @param null $previousStatus
     * @return bool
     */
    private function updateOrderPaymentStatusBySessionId($status, $previousStatus = null)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_payu_payments
			SET id_order = "' . (int)$this->id_order . '", status = "' . pSQL($status) . '", update_at = NOW()
			WHERE id_session="' . pSQL($this->payu_order_id) . '" AND status != "' . OpenPayuOrderStatus::STATUS_COMPLETED . '" AND status != "' . pSQL($status) . '"';

        if ($previousStatus) {
            $sql .= ' AND status = "' . $previousStatus . '"';
        }

        SimplePayuLogger::addLog('notification', __FUNCTION__, $sql, $this->payu_order_id);

        return Db::getInstance()->execute($sql);
    }

    private function checkIfStatusCompleted($id_session)
    {
        $result = Db::getInstance()->getRow('
			SELECT status FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE id_session = "' . addslashes($id_session) . '"');

        return $result['status'] == OpenPayuOrderStatus::STATUS_COMPLETED;
    }

    /**
     * @param int | null $idCustomer
     * @return array | null
     */
    private function getCustomer($idCustomer)
    {
        if (!$idCustomer) {
            return null;
        }

        $customer = new Customer((int)$idCustomer);

        if (!$customer->email) {
            return null;
        }

        return array(
            'email' => $customer->email,
            'firstName' => $customer->firstname,
            'lastName' => $customer->lastname,
            'language' => Language::getIsoById($this->context->language->id)
        );
    }

    private function reorderPaymentMethods($payMethods)
    {
        foreach ($payMethods as $id => $payMethod) {
            if ($payMethod->value == 'c') {
                $cart = $payMethod;
                unset($payMethods[$id]);
                array_unshift($payMethods, $cart);
            }
            if ($payMethod->value == 't' && $payMethod->status != 'ENABLED') {
                unset($payMethods[$id]);
            }
        }

        $paymentMethodsOrder = explode(',', str_replace(' ', '', Configuration::get('PAYU_PAYMENT_METHODS_ORDER')));

        if (count($paymentMethodsOrder) > 0) {
            array_walk(
                $payMethods,
                function ($item, $key, $paymentMethodsOrder) {
                    if (array_key_exists($item->value, $paymentMethodsOrder)) {
                        $item->sort = $paymentMethodsOrder[$item->value];
                    } else {
                        $item->sort = $key + 100;
                    }
                },
                array_flip($paymentMethodsOrder)
            );
            usort(
                $payMethods,
                function ($a, $b) {
                    return $a->sort - $b->sort;
                }
            );
        }

        return $payMethods;
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

    private function configureOpuByIdOrder($idOrder)
    {
        $order = new Order($idOrder);
        $currency = Currency::getCurrency($order->id_currency);
        $this->initializeOpenPayU($currency['iso_code']);
    }

    /**
     * @param $status
     * @return bool
     */
    private function updateOrderState($status)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Entrance: ', $this->payu_order_id);

        if (!empty($this->order->id) && !empty($status)) {
            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Payu order status: ' . $status, $this->payu_order_id);
            if ($this->checkIfStatusCompleted($this->payu_order_id)) {
                return true;
            }
            $order_state_id = $this->order->current_state;

            $history = new OrderHistory();
            $history->id_order = $this->order->id;

            $withoutUpdateOrderState = $this->hasLastPayuOrderIsCompleted($this->order->id);

            switch ($status) {
                case OpenPayuOrderStatus::STATUS_COMPLETED:
                    if (!$withoutUpdateOrderState && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')) {
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    $this->updateOrderPaymentStatusBySessionId($status);
                    break;
                case OpenPayuOrderStatus::STATUS_CANCELED:
                    if (!$withoutUpdateOrderState && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')) {
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    $this->updateOrderPaymentStatusBySessionId($status);
                    break;
                case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
                case OpenPayuOrderStatus::STATUS_REJECTED:
                    if (!$withoutUpdateOrderState && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_SENT')) {
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_SENT'), $this->order->id);
                        $history->addWithemail(true);
                    }
                    $this->updateOrderPaymentStatusBySessionId($status);
                    break;
                case OpenPayuOrderStatus::STATUS_PENDING:
                    $this->updateOrderPaymentStatusBySessionId($status, OpenPayuOrderStatus::STATUS_NEW);
                    break;
            }
        }

        return false;
    }

    /**
     * @return array|null
     */
    private function getStatesList()
    {
        $states = OrderState::getOrderStates($this->context->language->id);

        if (!is_array($states) || count($states) == 0) {
            return null;
        }

        $list = array();
        foreach ($states as $state) {
            $list[] = array(
                'id' => $state['id_order_state'],
                'name' => $state['name']
            );
        }

        return $list;
    }

    /**
     * @return array
     */
    private function getPaymentAcceptanceStatusesList()
    {
        return array(
            array('id' => OpenPayuOrderStatus::STATUS_COMPLETED, 'name' => $this->l('Accept the payment')),
            array('id' => OpenPayuOrderStatus::STATUS_CANCELED, 'name' => $this->l('Reject the payment'))
        );
    }

    /**
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
     * @return bool
     */
    private function createHooks()
    {
        $registerStatus = $this->registerHook('header') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('adminOrder') &&
            $this->registerHook('displayOrderDetail');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('displayPaymentEU') && $this->registerHook('payment');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    /**
     * @param $status
     * @return array | bool
     */
    private function sendPaymentUpdate($status = null)
    {
        $this->configureOpuByIdOrder($this->id_order);

        if (!empty($status) && !empty($this->payu_order_id)) {

            try {
                if ($status == OpenPayuOrderStatus::STATUS_CANCELED) {
                    $result = OpenPayU_Order::cancel($this->payu_order_id);
                } elseif ($status == OpenPayuOrderStatus::STATUS_COMPLETED) {
                    $status_update = array(
                        "orderId" => $this->payu_order_id,
                        "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                    );
                    $result = OpenPayU_Order::statusUpdate($status_update);
                }
            } catch (OpenPayU_Exception $e) {
                return array(
                    'message' => $e->getMessage()
                );
            }

            if ($result->getStatus() == 'SUCCESS') {
                return true;
            } else {
                return array(
                    'message' => $result->getError() . ' ' . $result->getMessage()
                );
            }
        }
        return array(
            'message' => $this->l('Order status update hasn\'t been sent')
        );
    }

    /**
     * @param string $state
     * @param array $names
     * @return bool
     */
    public function addNewOrderState($state, $names)
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

    private function payuOrderRefund($value, $ref_no, $id_order)
    {
        $this->configureOpuByIdOrder($id_order);

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

    private function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function buildTemplatePath($name)
    {
        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            return $name . '.tpl';
        }
        return 'module:payu/views/templates/front/' . $name . '17.tpl';
    }

    private function getVersion()
    {
        return 'Prestashop ver ' . _PS_VERSION_ . '/Plugin ver ' . $this->version;
    }
}
