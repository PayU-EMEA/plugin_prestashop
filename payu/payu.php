<?php
/**
 * PayU module
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */


if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/PayUSDKInitializer.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayMethodsCache/PayMethodsCache.php');


class PayU extends PaymentModule
{

    const CONDITION_PL = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf';
    const CONDITION_EN = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_en.pdf';
    const CONDITION_CS = 'http://static.payu.com/sites/terms/files/Podmínky pro provedení jednorázové platební transakce v PayU.pdf';

    const PAYU_MIN_CREDIT_AMOUNT = 300;
    const PAYU_MAX_CREDIT_AMOUNT = 20000;
    const PAYU_MIN_DP_AMOUNT = 100;
    const PAYU_MAX_DP_AMOUNT = 2000;

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
        $this->version = '3.1.6';
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
            Configuration::updateValue('PAYU_PAY_BY_ICON_CLICK', 0) &&
            Configuration::updateValue('PAYU_SANDBOX', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', 0) &&
            Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', '') &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', 1) &&
            Configuration::updateValue('PAYU_STATUS_CONTROL', 0)
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
            !Configuration::deleteByName('PAYU_PAY_BY_ICON_CLICK') ||
            !Configuration::deleteByName('PAYU_SANDBOX') ||
            !Configuration::deleteByName('PAYU_SEPARATE_CARD_PAYMENT') ||
            !Configuration::deleteByName('PAYU_CARD_PAYMENT_WIDGET') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_ORDER') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_CART') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_SUMMARY') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_PRODUCT') ||
            !Configuration::deleteByName('PAYU_STATUS_CONTROL')
        ) {
            return false;
        }
        return true;
    }


    public function initializeOpenPayU($currencyIsoCode)
    {
        $sdkInitializer = new PayUSDKInitializer();
        return $sdkInitializer->initializeOpenPayU($currencyIsoCode, $this->getVersion());
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
                $PAYU_MC_POS_ID[$currency['iso_code']] = Tools::getValue('PAYU_MC_POS_ID|' . $currency['iso_code']);
                $PAYU_MC_SIGNATURE_KEY[$currency['iso_code']] = Tools::getValue('PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']);
                $PAYU_MC_OAUTH_CLIENT_ID[$currency['iso_code']] = Tools::getValue('PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']);
                $PAYU_MC_OAUTH_CLIENT_SECRET[$currency['iso_code']] = Tools::getValue('PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']);
                $SANDBOX_PAYU_MC_POS_ID[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_POS_ID|' . $currency['iso_code']);
                $SANDBOX_PAYU_MC_SIGNATURE_KEY[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']);
                $SANDBOX_PAYU_MC_OAUTH_CLIENT_ID[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']);
                $SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET[$currency['iso_code']] = Tools::getValue('SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']);
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
                !Configuration::updateValue('PAYU_PAY_BY_ICON_CLICK', (Tools::getValue('PAYU_PAY_BY_ICON_CLICK') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SANDBOX', (Tools::getValue('PAYU_SANDBOX') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', (Tools::getValue('PAYU_SEPARATE_CARD_PAYMENT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', (Tools::getValue('PAYU_CARD_PAYMENT_WIDGET') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', Tools::getValue('PAYU_PAYMENT_METHODS_ORDER')) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT', (Tools::getValue('PAYU_PROMOTE_CREDIT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', (Tools::getValue('PAYU_PROMOTE_CREDIT_CART') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', (Tools::getValue('PAYU_PROMOTE_CREDIT_SUMMARY') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', (Tools::getValue('PAYU_PROMOTE_CREDIT_PRODUCT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_STATUS_CONTROL', (Tools::getValue('PAYU_STATUS_CONTROL') ? 1 : 0))
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
                        'type' => 'switch',
                        'label' => $this->l('Pay by click on bank icon button'),
                        'name' => 'PAYU_PAY_BY_ICON_CLICK',
                        'disabled' => (Tools::getValue('PAYU_RETRIEVE', Configuration::get('PAYU_RETRIEVE'))) ? false : true,
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
                        'type' => 'switch',
                        'label' => $this->l('Separate card payment'),
                        'name' => 'PAYU_SEPARATE_CARD_PAYMENT',
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
                        'type' => 'switch',
                        'label' => $this->l('Card payment on widget'),
                        'desc' => $this->l('Card tokenization must be enabled - https://github.com/PayU-EMEA/plugin_prestashop/blob/master/README.EN.md#card-widget'),
                        'name' => 'PAYU_CARD_PAYMENT_WIDGET',
                        'disabled' => (Tools::getValue('PAYU_SEPARATE_CARD_PAYMENT', Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'))) ? false : true,
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

        $form['installments'] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Installments'),
                    'icon' => 'icon-tag'
                ),
                'input' => array_merge(array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Promote credit payment methods'),
                        'desc' => $this->l('Enables credit payment methods on summary and enables promoting installments'),
                        'name' => 'PAYU_PROMOTE_CREDIT',
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
                    )),
                    !version_compare(_PS_VERSION_, '1.7', 'lt')? array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show installment on cart'),
                            'desc' => $this->l('Promotes credit payment method on cart'),
                            'name' => 'PAYU_PROMOTE_CREDIT_CART',
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
                            'type' => 'switch',
                            'label' => $this->l('Show installment on summary'),
                            'desc' => $this->l('Promotes credit payment method on summary'),
                            'name' => 'PAYU_PROMOTE_CREDIT_SUMMARY',
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
                        )
                    ):array(),
                    array(array(
                        'type' => 'switch',
                        'label' => $this->l('Show installments on product'),
                        'desc' => $this->l('Promotes credit payment method on product'),
                        'name' => 'PAYU_PROMOTE_CREDIT_PRODUCT',
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

                    )),
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
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Control of status changes'),
                        'desc' => $this->l('For status "Complete" and "Canceled" it is possible to switch only from the status "Pending" and "Waiting For Confirmation"'),
                        'name' => 'PAYU_STATUS_CONTROL',
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

        $helper = new HelperForm();
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->show_toolbar = false;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

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
            'PAYU_PAY_BY_ICON_CLICK' => Configuration::get('PAYU_PAY_BY_ICON_CLICK'),
            'PAYU_SANDBOX' => Configuration::get('PAYU_SANDBOX'),
            'PAYU_SEPARATE_CARD_PAYMENT' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'),
            'PAYU_CARD_PAYMENT_WIDGET' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET'),
            'PAYU_PAYMENT_METHODS_ORDER' => Configuration::get('PAYU_PAYMENT_METHODS_ORDER'),
            'PAYU_PROMOTE_CREDIT' => Configuration::get('PAYU_PROMOTE_CREDIT'),
            'PAYU_PROMOTE_CREDIT_CART' => Configuration::get('PAYU_PROMOTE_CREDIT_CART'),
            'PAYU_PROMOTE_CREDIT_SUMMARY' => Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY'),
            'PAYU_PROMOTE_CREDIT_PRODUCT' => Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT'),
            'PAYU_STATUS_CONTROL' => Configuration::get('PAYU_STATUS_CONTROL')
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

    private function ParseConfigByCurrency($key, $currency)
    {
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
        $output = '<link type="text/css" rel="stylesheet" href="' . _MODULE_DIR_ . $this->name . '/css/payu.css" /><script type="text/javascript" src="https://static.payu.com/res/v2/prestashop-plugin.js"></script>';

        $vieworder = Tools::getValue('vieworder');
        $id_order = Tools::getValue('id_order');

        $refundable = false;

        if ($vieworder !== false && $id_order !== false) {
            $order = new Order($id_order);
            $order_payment = $this->getLastOrderPaymentByOrderId($id_order);

            if ($order->module === 'payu' && $order_payment['status'] === OpenPayuOrderStatus::STATUS_COMPLETED) {
                $refundable = true;
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
                    if ($refund[0] !== true) {
                        $refund_errors[] = $this->l('Refund error: ') . $refund[1];
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

        if(Configuration::get('PAYU_PROMOTE_CREDIT') === '1') {
            if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
                $this->context->controller->addCSS('https://static.payu.com/res/v2/layout/style.css', 'all');
                $this->context->controller->addJS('https://static.payu.com/res/v2/widget-products-installments.js', 'all');
            } else {
                $this->context->controller->registerJavascript(
                    'remote-widget-products-installments',
                    'https://static.payu.com/res/v2/widget-products-installments.js',
                    ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
                $this->context->controller->registerStylesheet(
                    'remote-installments-css-payu',
                    'https://static.payu.com/res/v2/layout/style.css',
                    ['server' => 'remote', 'media' => 'all', 'priority' => 20]);
            }
        }
    }


    public function hookDisplayOrderDetail($params)
    {
        if ($this->hasRetryPayment($params['order']->id, $params['order']->current_state)) {
            $this->context->smarty->assign(
                array(
                    'payuImage' => $this->getPayuLogo('payu_logo_small.png'),
                    'payuActionUrl' => $this->context->link->getModuleLink(
                        'payu', 'payment', array('id_order' => $params['order']->id, 'order_reference' => $params['order']->reference)
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

        $cart = $params['cart'];
        $totalPrice = $cart->getOrderTotal();


        $paymentOptions = [];

        if (Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable()) {
            $cardPaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $cardPaymentOption->setCallToActionText($this->l('Pay by card'))
                ->setAdditionalInformation('<span class="payu-marker-class"></span>')
                ->setModuleName($this->name)
                ->setLogo($this->getPayuLogo('payu_cards.png'))
                ->setAction(
                    Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1'
                        ? $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'card'])
                        : $this->context->link->getModuleLink($this->name, 'payment', ['payuPay' => 1, 'payMethod' => 'c', 'payuConditions' => true])
                );

            array_push($paymentOptions, $cardPaymentOption);
        }

        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText(empty($paymentOptions) ? $this->l('Pay by online transfer or card') :  $this->l('Pay by online transfer'))
            ->setAdditionalInformation('<span class="payu-marker-class"></span>')
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'payment'));

        if (Configuration::get('PAYU_PROMOTE_CREDIT') !== '1' ||
            !($this->isCreditAvailable($totalPrice) || $this->isPayULaterAvailable($totalPrice))) {
            $paymentOption->setLogo($this->getPayuLogo('payu_logo_small.png'));
        }

        array_push($paymentOptions, $paymentOption);

        if ($this->isCreditAvailable($totalPrice) || $this->isPayULaterAvailable($totalPrice)) {
            $this->context->smarty->assign(array(
                'total_price' => $totalPrice,
                'payu_installment_img' => $this->getPayuLogo('payu_installment.png'),
                'payu_logo_img' => $this->getPayuLogo('payu_logo_small.png'),
                'payu_later_logo_img' => $this->getPayuLogo('payu_later_logo.png'),
                'payu_question_mark_img' => $this->getPayuLogo('question_mark.png'),
            ));

            if ($this->isCreditAvailable($totalPrice)) {
                $installmentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $installmentOption
                    ->setCallToActionText($this->l('Pay online in installments'))
                    ->setModuleName($this->name)
                    ->setAdditionalInformation($this->fetchTemplate('checkout_installment.tpl'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        array('payuPay' => 1, 'payMethod' => 'ai', 'payuConditions' => true)));
                array_push($paymentOptions, $installmentOption);
            }

            if ($this->isPayULaterAvailable($totalPrice)) {
                $payULaterOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $payULaterOption
                    ->setCallToActionText($this->l('Pay later with PayU'))
                    ->setModuleName($this->name)
                    ->setAdditionalInformation($this->fetchTemplate('checkout_payu_later.tpl'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        array('payuPay' => 1, 'payMethod' => 'dp', 'payuConditions' => true)));
                array_push($paymentOptions, $payULaterOption);
            }
        }

        return $paymentOptions;
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookPayment($params)
    {
        $this->context->smarty->assign(array(
                'image' => $this->getPayuLogo(),
                'creditImage' => $this->getPayuLogo('raty_small.png'),
                'payu_logo_img' => $this->getPayuLogo('payu_logo_small.png'),
                'showCardPayment' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable(),
                'showWidget' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1',
                'actionUrl' => $this->context->link->getModuleLink('payu', 'payment'),
                'cardActionUrl' => (Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1'
                    ? $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'card'])
                    : $this->context->link->getModuleLink($this->name, 'payment', ['payuPay' => 1, 'payMethod' => 'c', 'payuConditions' => true])),
                'creditActionUrl' => $this->context->link->getModuleLink('payu', 'payment', array(
                    'payuPay' => 1, 'payMethod' => 'ai', 'payuConditions' => true
                )),
                'creditPayULaterActionUrl' => $this->context->link->getModuleLink('payu', 'payment', array(
                    'payuPay' => 1, 'payMethod' => 'dp', 'payuConditions' => true
                )),
                'credit_available' => $this->isCreditAvailable($params['cart']->getOrderTotal()),
                'payu_later_available' => $this->isPayULaterAvailable($params['cart']->getOrderTotal()),
                'cart_total_amount' => $params['cart']->getOrderTotal())
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
            'PAYU_PAYMENT_ACCEPT' => false,
            'IS_17' => $this->is17()
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
            ($payuOrder['status'] == OpenPayuOrderStatus::STATUS_CANCELED && $order_state == (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')))) {
            return true;
        }
        return false;
    }

    /**
     * @param null|string $payMethod
     * @return array
     * @throws Exception
     */
    public function orderCreateRequestByOrder($payMethod = null, $parameters = [])
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
            'continueUrl' => $this->context->link->getModuleLink('payu', 'success', array('id' => $this->extOrderId)),
            'currencyCode' => $currency['iso_code'],
            'totalAmount' => $this->toAmount($this->order->total_paid),
            'extOrderId' => $this->extOrderId
        );


        if ($this->getCustomer($this->order->id_customer)) {
            $ocreq['buyer'] = $this->getCustomer($this->order->id_customer);
        }

        if ($payMethod !== null) {
            if ($payMethod === 'card') {
                $ocreq['payMethods'] = array(
                    'payMethod' => array(
                        'type' => 'CARD_TOKEN',
                        'value' => $parameters['cardToken']
                    )
                );
            } else {
                $ocreq['payMethods'] = array(
                    'payMethod' => array(
                        'type' => 'PBL',
                        'value' => $payMethod
                    )
                );
            }
        }

        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id, 'OrderCreateRequest: ');
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderCreateResponse: ');

            if ($result->getStatus() === 'SUCCESS' || $result->getStatus() === 'WARNING_CONTINUE_3DS') {
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
        $payu_properties = isset($response->properties) ? $response->properties : null;

        if ($payu_order) {

            $this->order = new Order($this->id_order);
            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Order exists in PayU system ', $this->payu_order_id);
            $this->updateOrderState($payu_order, $payu_properties);
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
        switch ($this->getLanguage()) {
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

            $retreive = OpenPayU_Retrieve::payMethods($this->getLanguage());
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
            'language' => $this->getLanguage()
        );
    }

    private function reorderPaymentMethods($payMethods)
    {
        $filteredPaymethods = [];
        foreach ($payMethods as $id => $payMethod) {
            if ($payMethod->value == 'c') {
                array_unshift($filteredPaymethods, $payMethod);
            } else {
                if ($payMethod->value !== 't' || ($payMethod->value === 't' && $payMethod->status === 'ENABLED')
                ) {
                    $filteredPaymethods[] = $payMethod;
                }
            }
        }

        $paymentMethodsOrder = explode(',', str_replace(' ', '', Configuration::get('PAYU_PAYMENT_METHODS_ORDER')));

        if (count($paymentMethodsOrder) > 0) {
            array_walk(
                $filteredPaymethods,
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
                $filteredPaymethods,
                function ($a, $b) {
                    return $a->sort - $b->sort;
                }
            );
        }

        return $filteredPaymethods;
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

    private function getLanguage()
    {
        $iso = Language::getIsoById($this->context->language->id);

        return $iso === 'gb' ? 'en' : $iso;
    }

    private function configureOpuByIdOrder($idOrder)
    {
        $order = new Order($idOrder);
        $currency = Currency::getCurrency($order->id_currency);
        $this->initializeOpenPayU($currency['iso_code']);
    }

    /**
     * @param object $payu_order
     * @return bool
     */
    private function updateOrderState($payu_order, $payu_properties)
    {
        $status = isset($payu_order->status) ? $payu_order->status : null;

        SimplePayuLogger::addLog('notification', __FUNCTION__, 'Entrance: ', $this->payu_order_id);

        if (!empty($this->order->id) && !empty($status)) {
            SimplePayuLogger::addLog('notification', __FUNCTION__, 'Payu order status: ' . $status, $this->payu_order_id);
            if ($this->checkIfStatusCompleted($this->payu_order_id)) {
                return true;
            }
            $order_state_id = $this->order->current_state;

            $history = new OrderHistory();
            $history->id_order = $this->order->id;

            $withoutUpdateOrderState = !$this->isCorrectPreviousStatus($order_state_id)
                || $this->hasLastPayuOrderIsCompleted($this->order->id);

            switch ($status) {
                case OpenPayuOrderStatus::STATUS_COMPLETED:
                    if (!$withoutUpdateOrderState && $order_state_id != (int)Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')) {
                        $history->changeIdOrderState(Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'), $this->order->id);
                        $history->addWithemail(true);
                        $this->addTransactionIdToPayment($this->order, $this->getTransactionId($payu_properties));
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
     * @param $status
     * @return bool
     */
    private function isCorrectPreviousStatus($status)
    {
        if (Configuration::get('PAYU_STATUS_CONTROL') !== '1') {
            return true;
        }

        return $status == Configuration::get('PAYU_PAYMENT_STATUS_PENDING') || $status == Configuration::get('PAYU_PAYMENT_STATUS_SENT');

    }

    /**
     * @param Order $order
     * @param $transactionId
     */
    private function addTransactionIdToPayment($order, $transactionId)
    {
        if ($transactionId === null) {
            return;
        }
        $payments = $order->getOrderPaymentCollection()->getResults();
        if (count($payments) > 0) {
            $payments[0]->transaction_id = $transactionId;
            $payments[0]->update();
        }
    }

    /**
     * @param $payu_properties
     * @return string
     */
    private function getTransactionId($payu_properties)
    {
        return $payu_properties !== null ? $this->extractPaymentIdFromProperties($payu_properties) : null;
    }

    /**
     * @param array $properties
     * @return string
     */
    private function extractPaymentIdFromProperties($properties)
    {
        if (is_array($properties)) {
            foreach ($properties as $property) {
                if ($property->name === 'PAYMENT_ID') {
                    return $property->value;
                }
            }
        }
        return null;
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
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayCheckoutSubtotalDetails') &&
            $this->registerHook('displayCheckoutSummaryTop');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('displayPaymentEU') && $this->registerHook('payment');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    public function hookDisplayCheckoutSubtotalDetails($params)
    {
        if ($this->isCreditAvailable($params['cart']->getOrderTotal())
            && Configuration::get('PAYU_PROMOTE_CREDIT_CART') === '1') {
            $this->context->smarty->assign(array(
                'cart_total_amount' => $params['cart']->getOrderTotal()
            ));
            return $this->display(__FILE__, 'cart-detailed-totals.tpl');
        }
    }

    public function hookDisplayCheckoutSummaryTop($params)
    {
        if (Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY') === '1' &&
            $this->isCreditAvailable($params['cart']->getOrderTotal())) {
            $this->context->smarty->assign(array(
                'cart_total_amount' => $params['cart']->getOrderTotal()
            ));
            return $this->display(__FILE__, 'cart-summary.tpl');
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if (!PayMethodsCache::isInstallmentsAvailable(
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion()) ||
            Configuration::get('PAYU_PROMOTE_CREDIT') === '0' || Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT') === '0') {
            return;
        }

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $showInView = 'weight';
            $current_controller = Tools::getValue('controller');
            if ($current_controller === 'index') {
                $showInView = "unit_price";
            }
            if ($params['type'] === $showInView) {
                $product = $params['product'];
                $price = null;
                $productId = null;
                if (is_array($product)) {
                    $price = $product['price'];
                    $productId = $product['id_product'];
                } else {
                    $price = $product->getPrice();
                    $productId = $product->reference;
                }

                $creditAvailable = false;
                $priceWithDot = str_replace(',', '.', $price);
                if($priceWithDot >= self::PAYU_MIN_CREDIT_AMOUNT &&
                    $priceWithDot <= self::PAYU_MAX_CREDIT_AMOUNT) {
                    $creditAvailable = true;
                }

                if($creditAvailable){
                    $this->context->smarty->assign(array(
                        'product_price' => $price,
                        'product_id' => $productId
                    ));
                    return $this->display(__FILE__, 'product.tpl');
                } else {
                    return;
                }

            }
        } else {
            $product = $params['product'];
            $current_controller = Tools::getValue('controller');
            $creditAvailable = isset($product['price_amount'])
                && ($product['price_amount'] >= self::PAYU_MIN_CREDIT_AMOUNT)
                && ($product['price_amount'] <= self::PAYU_MAX_CREDIT_AMOUNT);
            if ($creditAvailable && (($params['type'] === 'weight' && $current_controller === 'index') ||
                    ($params['type'] === 'after_price' && $current_controller === 'product'))) {
                $this->context->smarty->assign(array(
                    'product_price' => $product['price_amount'],
                    'product_id' => $product['id_product']
                ));
                return $this->display(__FILE__, 'product.tpl', $this->getCacheId($product['price_amount'].$product['id_product']));
            } else {
                return;
            }
        }
    }

    public function getPayuUrl($sandbox = false)
    {
        return 'https://secure.' . ($sandbox === true ? 'snd.' : '') . 'payu.com/';
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

        } catch (OpenPayU_Exception_Request $e) {
            $response = $e->getOriginalResponse()->getResponse()->status;
            Logger::addLog($this->displayName . ' Order Refund error: ' . $response->codeLiteral .' ['.$response->code.']', 1);
            return array(false, $response->codeLiteral .' ['.$response->code.'] - <a target="_blank" href="http://developers.payu.com/pl/restapi.html#refunds">developers.payu.com</a>');
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

    /**
     * @return bool
     */
    private function is17()
    {
        return !version_compare(_PS_VERSION_, '1.7', 'lt');
    }

    /**
     * @param $amount
     * @return bool
     */
    private function isCreditAvailable($amount)
    {
        return Configuration::get('PAYU_PROMOTE_CREDIT') === '1'
            && $amount >= self::PAYU_MIN_CREDIT_AMOUNT
            && $amount <= self::PAYU_MAX_CREDIT_AMOUNT
            && PayMethodsCache::isInstallmentsAvailable(
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion());
    }

    /**
     * @param $amount
     * @return bool
     */
    private function isCardAvailable()
    {
        return Configuration::get('PAYU_RETRIEVE') !== '1'
            || PayMethodsCache::isPaytypeAvailable('c',
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion(), true);
    }

    /**
     * @param $amount
     * @return bool
     */
    private function isPayULaterAvailable($amount)
    {
        return Configuration::get('PAYU_PROMOTE_CREDIT') === '1'
            && $amount >= self::PAYU_MIN_DP_AMOUNT
            && $amount <= self::PAYU_MAX_DP_AMOUNT
            && PayMethodsCache::isDelayedPaymentAvailable(
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion());
    }
}
