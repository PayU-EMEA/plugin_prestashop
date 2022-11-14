<?php
/**
 * PayU module
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
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
        $this->version = '3.2.6';
        $this->author = 'PayU';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.6.0', 'max' => '8.999'];

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
            $this->createPayUHistoryTable() &&
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
                ['en' => 'PayU payment pending', 'pl' => 'Płatność PayU rozpoczęta', 'cs' => 'Transakce PayU je zahájena'])) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', $this->addNewOrderState('PAYU_PAYMENT_STATUS_SENT',
                ['en' => 'PayU payment waiting for confirmation', 'pl' => 'Płatność PayU oczekuje na odbiór', 'cs' => 'Transakce  čeká na přijetí'])) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', $this->addNewOrderState('PAYU_PAYMENT_STATUS_CANCELED',
                ['en' => 'PayU payment canceled', 'pl' => 'Płatność PayU anulowana', 'cs' => 'Transakce PayU zrušena'])) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', 2) &&
            Configuration::updateValue('PAYU_REPAY', 0) &&
            Configuration::updateValue('PAYU_SANDBOX', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', 0) &&
            Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', '') &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', 1) &&
            Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_TWISTO', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_BLIK_PAYMENT', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_GRID', 0)
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
            !Configuration::deleteByName('PAYU_REPAY') ||
            !Configuration::deleteByName('PAYU_SANDBOX') ||
            !Configuration::deleteByName('PAYU_SEPARATE_CARD_PAYMENT') ||
            !Configuration::deleteByName('PAYU_CARD_PAYMENT_WIDGET') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_ORDER') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_CART') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_SUMMARY') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_PRODUCT') ||
            !Configuration::deleteByName('PAYU_SEPARATE_PAY_LATER_TWISTO') ||
            !Configuration::deleteByName('PAYU_SEPARATE_BLIK_PAYMENT') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_GRID', 0)
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
        $errors = [];

        if (Tools::isSubmit('submit' . $this->name)) {

            $PAYU_MC_POS_ID = [];
            $PAYU_MC_SIGNATURE_KEY = [];
            $PAYU_MC_OAUTH_CLIENT_ID = [];
            $PAYU_MC_OAUTH_CLIENT_SECRET = [];

            $SANDBOX_PAYU_MC_POS_ID = [];
            $SANDBOX_PAYU_MC_SIGNATURE_KEY = [];
            $SANDBOX_PAYU_MC_OAUTH_CLIENT_ID = [];
            $SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET = [];

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
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_COMPLETED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_CANCELED')) ||
                !Configuration::updateValue('PAYU_REPAY', (Tools::getValue('PAYU_REPAY') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SANDBOX', (Tools::getValue('PAYU_SANDBOX') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', (Tools::getValue('PAYU_SEPARATE_CARD_PAYMENT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_BLIK_PAYMENT', (Tools::getValue('PAYU_SEPARATE_BLIK_PAYMENT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', (Tools::getValue('PAYU_CARD_PAYMENT_WIDGET') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', Tools::getValue('PAYU_PAYMENT_METHODS_ORDER')) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT', (Tools::getValue('PAYU_PROMOTE_CREDIT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', (Tools::getValue('PAYU_PROMOTE_CREDIT_CART') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', (Tools::getValue('PAYU_PROMOTE_CREDIT_SUMMARY') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', (Tools::getValue('PAYU_PROMOTE_CREDIT_PRODUCT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_TWISTO', (Tools::getValue('PAYU_SEPARATE_PAY_LATER_TWISTO') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_GRID', (Tools::getValue('PAYU_PAYMENT_METHODS_GRID') ? 1 : 0))

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
        $form['method'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Integration method'),
                    'icon' => 'icon-th'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Bank list'),
                        'desc' => $this->l('Show payment methods in grid'),
                        'name' => 'PAYU_PAYMENT_METHODS_GRID',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Payment Methods Order'),
                        'name' => 'PAYU_PAYMENT_METHODS_ORDER',
                        'desc' => $this->l('Enter payment methods values separated by comma. List of payment methods - http://developers.payu.com/pl/overview.html#paymethods'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Separate card payment'),
                        'name' => 'PAYU_SEPARATE_CARD_PAYMENT',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Card payment on widget'),
                        'desc' => $this->l('Card tokenization must be enabled - https://github.com/PayU-EMEA/plugin_prestashop/blob/master/README.EN.md#card-widget'),
                        'name' => 'PAYU_CARD_PAYMENT_WIDGET',
                        'disabled' => (Tools::getValue('PAYU_SEPARATE_CARD_PAYMENT', Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'))) ? false : true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Separate BLIK payment'),
                        'name' => 'PAYU_SEPARATE_BLIK_PAYMENT',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('PayU repayment'),
                        'name' => 'PAYU_REPAY',
                        'desc' => $this->l('Before enabling repayment, read https://github.com/PayU-EMEA/plugin_prestashop#ponawianie-płatności and disable automatic collection in POS configuration.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('SANDBOX mode'),
                        'name' => 'PAYU_SANDBOX',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ]
        ];

        $form['installments'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Installments'),
                    'icon' => 'icon-tag'
                ],
                'input' => array_merge([
                    [
                        'type' => 'switch',
                        'label' => $this->l('Promote credit payment methods'),
                        'desc' => $this->l('Enables credit payment methods on summary and enables promoting installments'),
                        'name' => 'PAYU_PROMOTE_CREDIT',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Separate pay later Twisto'),
                        'desc' => $this->l('Shows separate Twisto payment method'),
                        'name' => 'PAYU_SEPARATE_PAY_LATER_TWISTO',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ]
                ],
                    $this->is17() ? [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Show installment on cart'),
                            'desc' => $this->l('Promotes credit payment method on cart'),
                            'name' => 'PAYU_PROMOTE_CREDIT_CART',
                            'values' => [
                                [
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ],
                                [
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ]
                            ],
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Show installment on summary'),
                            'desc' => $this->l('Promotes credit payment method on summary'),
                            'name' => 'PAYU_PROMOTE_CREDIT_SUMMARY',
                            'values' => [
                                [
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ],
                                [
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ]
                            ],
                        ]
                    ] : [],
                    [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Show installments on product'),
                            'desc' => $this->l('Promotes credit payment method on product'),
                            'name' => 'PAYU_PROMOTE_CREDIT_PRODUCT',
                            'values' => [
                                [
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ],
                                [
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ]
                            ],
                        ],

                    ]),
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ]
        ];

        foreach (Currency::getCurrencies() as $currency) {
            $form['pos_' . $currency['iso_code']] = [
                'form' => [
                    'legend' => [
                        'title' => $this->l('POS settings - currency: ') . $currency['name'] . ' (' . $currency['iso_code'] . ')',
                        'icon' => 'icon-cog'
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('POS ID'),
                            'name' => 'PAYU_MC_POS_ID|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Second key (MD5)'),
                            'name' => 'PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_id'),
                            'name' => 'PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_secret'),
                            'name' => 'PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ]
                ]
            ];
            $form['sandbox_pos_' . $currency['iso_code']] = [
                'form' => [
                    'legend' => [
                        'title' => '<span style="color: red">' . $this->l('SANDBOX - ') . '</span>' . $this->l('POS settings - currency: ') . $currency['name'] . ' (' . $currency['iso_code'] . ')',
                        'icon' => 'icon-cog'
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('POS ID'),
                            'name' => 'SANDBOX_PAYU_MC_POS_ID|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Second key (MD5)'),
                            'name' => 'SANDBOX_PAYU_MC_SIGNATURE_KEY|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_id'),
                            'name' => 'SANDBOX_PAYU_MC_OAUTH_CLIENT_ID|' . $currency['iso_code']
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('OAuth - client_secret'),
                            'name' => 'SANDBOX_PAYU_MC_OAUTH_CLIENT_SECRET|' . $currency['iso_code']
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ]
                ]
            ];
        }

        $form['statuses'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Payment statuses'),
                    'icon' => 'icon-tag'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Pending status'),
                        'name' => 'PAYU_PAYMENT_STATUS_PENDING',
                        'options' => [
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Complete status'),
                        'name' => 'PAYU_PAYMENT_STATUS_COMPLETED',
                        'options' => [
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Canceled status'),
                        'name' => 'PAYU_PAYMENT_STATUS_CANCELED',
                        'options' => [
                            'query' => $this->getStatesList(),
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ]
        ];

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

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm($form);
    }

    private function getConfigFieldsValues()
    {

        $config = [
            'PAYU_PAYMENT_STATUS_PENDING' => Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            'PAYU_PAYMENT_STATUS_COMPLETED' => Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'),
            'PAYU_PAYMENT_STATUS_CANCELED' => Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'),
            'PAYU_REPAY' => Configuration::get('PAYU_REPAY'),
            'PAYU_SANDBOX' => Configuration::get('PAYU_SANDBOX'),
            'PAYU_SEPARATE_CARD_PAYMENT' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'),
            'PAYU_SEPARATE_BLIK_PAYMENT' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
            'PAYU_CARD_PAYMENT_WIDGET' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET'),
            'PAYU_PAYMENT_METHODS_ORDER' => Configuration::get('PAYU_PAYMENT_METHODS_ORDER'),
            'PAYU_PROMOTE_CREDIT' => Configuration::get('PAYU_PROMOTE_CREDIT'),
            'PAYU_PROMOTE_CREDIT_CART' => Configuration::get('PAYU_PROMOTE_CREDIT_CART'),
            'PAYU_PROMOTE_CREDIT_SUMMARY' => Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY'),
            'PAYU_PROMOTE_CREDIT_PRODUCT' => Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT'),
            'PAYU_SEPARATE_PAY_LATER_TWISTO' => Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO'),
            'PAYU_PAYMENT_METHODS_GRID' => Configuration::get('PAYU_PAYMENT_METHODS_GRID'),

        ];

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

        return $output;
    }

    public function hookActionGetExtraMailTemplateVars(array &$params)
    {
        if (isset($params['template_vars']['{payment}'])
            && $this->displayName === substr($params['template_vars']['{payment}'],0, strlen($this->displayName))
            && $this->repaymentEnabled()
            && $params['template'] == 'order_conf'
        ) {
            if($this->is17()){
                $id_order = $params['template_vars']['{id_order}'];
            }
            else{
                $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference="' . $params['template_vars']['{order_name}'] . '" limit 1';
                $result = Db::getInstance()->ExecuteS($sql);
                $id_order = $result[0]['id_order'];
            }
            $params['extra_template_vars']['{payment}'] = 'PayU, <a href="' . $this->context->link->getPageLink('index',true) .'index.php?controller=order-detail&id_order=' . $id_order . '#repayment">' . $this->l('Repay by PayU') . '</a>';
        }
    }

    public function hookHeader()
    {
        $controller = Context::getContext()->controller->php_self;

        if (Configuration::get('PAYU_PROMOTE_CREDIT') === '1') {
            if ($this->is17()) {
                $this->context->controller->registerJavascript(
                    'remote-widget-products-installments',
                    'https://static.payu.com/res/v2/widget-mini-installments.js',
                    ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
                $this->context->controller->registerStylesheet(
                    'remote-installments-css-payu',
                    'https://static.payu.com/res/v2/layout/style.css',
                    ['server' => 'remote', 'media' => 'all', 'priority' => 20]);
            } else {
                $this->context->controller->addCSS('https://static.payu.com/res/v2/layout/style.css');
                $this->context->controller->addJS('https://static.payu.com/res/v2/widget-mini-installments.js');
            }
        }

        if($controller === 'order-opc' ||
           $controller === 'order' ||
           $controller === 'cart' ||
           $controller === 'product' ||
           $controller === 'order-detail' ||
           $controller === 'history'
        ) {

            if ($this->is17()) {
                $this->context->controller->registerStylesheet(
                    'payu-css',
                    'modules/' . $this->name . '/css/payu.css'
                );
                $this->context->controller->registerJavascript(
                    'payu-js',
                    'modules/' . $this->name . '/js/payu17.js'
                );
            } else {
                $this->context->controller->addJS(($this->_path) . 'js/payu.js');
                $this->context->controller->addCSS(($this->_path) . 'css/payu.css');
            }

            Media::addJsDef([
                'payuLangId' => $this->context->language->iso_code,
                'payuSFEnabled' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1' ? true : false,
            ]);
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        if ($this->hasRetryPayment($params['order']->id, $params['order']->current_state)) {
            $payMethods = $this->getPaymethods(Currency::getCurrency($params['order']->id_currency), $params['order']->total_paid);
            $retry_params = [
                'order_total' => $params['order']->total_paid,
                'id_order' => $params['order']->id,
                'order_reference' => $params['order']->reference,
                'is_retry' => true,
                'paymentMethods' => $payMethods
            ];
            $this->context->smarty->assign(
                [
                    'payuImage' => $this->getPayuLogo(),
                    'payMethods' => $payMethods,
                    'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment', ['id_order' => $params['order']->id, 'order_reference' => $params['order']->reference]),
                    'conditionUrl' => $this->getPayConditionUrl(),
                    'gateways' => $this->hookPaymentOptions($retry_params, true),
                    'payuActionUrl' => $this->context->link->getModuleLink(
                        'payu', 'payment', ['id_order' => $params['order']->id, 'order_reference' => $params['order']->reference]
                    )
                ]
            );

            return $this->fetchTemplate($this->is17() ? 'retryPayment17.tpl' : 'retryPayment.tpl');
        }
    }

    /**
     * Only for >=1.7
     *
     * @param $params
     *
     * @return array|void
     */
    public function hookPaymentOptions($params, $retry = false)
    {
        if (!$this->active) {
            return;
        }
        if (isset($params['cart'])) {
            if (!$this->checkCurrency($params['cart'])) {
                return;
            }
            $cart = $params['cart'];
            $totalPrice = $cart->getOrderTotal();
        } else {
            $totalPrice = $params['order_total'];
        }

        $paymentOptions = [];
        $retry16 = !$this->is17() && $retry;

        if ($retry) {
            $paymentMethods = $params['paymentMethods'];
        } else {
            $paymentMethods = $this->getPaymethods(Currency::getCurrency($this->context->cart->id_currency), $totalPrice);
        }

        $this->smarty->assign([
            'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
            'conditionUrl' => $this->getPayConditionUrl(),
            'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment'),
            'paymentMethods' => $paymentMethods['payByLinks'],
            'separateBlik' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
            'separateTwisto' => Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO'),
            'separateCard' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'),
            'posId' => OpenPayU_Configuration::getMerchantPosId(),
            'lang' => Language::getIsoById($this->context->language->id),
            'paymentId' => Tools::getValue('payment_id'),
            'params' => $params,
            'grid' => Configuration::get('PAYU_PAYMENT_METHODS_GRID'),
            'retryPayment' => $retry,
            'modulePath' => _PS_MODULE_DIR_ . 'payu',
            'has_sf' => false
        ]);

        $this->setPayuNotification();
        if (Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable()) {
            if (Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1') {
                $this->smarty->assign([
                    'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
                    'secureFormJsTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/secureFormJs.tpl',
                    'payCardTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/payuCardForm.tpl',
                    'conditionUrl' => $this->getPayConditionUrl(),
                    'jsSdk' => $this->getPayuUrl(Configuration::get('PAYU_SANDBOX') === '1') . 'javascript/sdk',
                    'posId' => OpenPayU_Configuration::getMerchantPosId(),
                    'lang' => Language::getIsoById($this->context->language->id),
                    'paymentId' => Tools::getValue('payment_id'),
                    'has_sf' => true
                ]);
            }
            if ($retry16) {
                $cardPaymentOption = [
                    'CallToActionText' => $this->l('Pay by card'),
                    'AdditionalInformation' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET') == 1 ? $this->fetchTemplate('secureForm16.tpl') : '<span class="payment-name" data-pm="c"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('card-visa-mc.svg'),
                ];
            } else {
                $cardPaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $cardPaymentOption->setCallToActionText($this->l('Pay by card'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('card-visa-mc.svg'));

                if (Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1') {
                    $cardPaymentOption->setAdditionalInformation($this->fetchTemplate('secureForm17.tpl'));
                } else {
                    if ($retry) {
                        $cardPaymentOption->setAdditionalInformation('<span class="payment-name" data-pm="c"></span>');
                    }
                    $cardPaymentOption->setAction($this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'c']));
                }
            }

            $paymentOptions[] = $cardPaymentOption;
        }

        if (Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT') === '1' && $this->isBlikAvailable()) {
            if ($retry16) {
                $blikPaymentOption = [
                    'CallToActionText' => $this->l('Pay by BLIK'),
                    'AdditionalInformation' => $this->fetchTemplate('conditions17.tpl') . '<span class="payment-name" data-pm="blik"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('blik.svg')
                ];
            } else {
                $blikPaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $blikPaymentOption->setCallToActionText($this->l('Pay by BLIK'))
                    ->setAdditionalInformation($this->fetchTemplate('conditions17.tpl') . '<span class="payment-name" data-pm="blik"></span>')
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('blik.svg'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => 'blik'
                        ]
                    ));
            }

            $paymentOptions[] = $blikPaymentOption;
        }

        if ($retry16) {
            $paymentOption = [
                'CallToActionText' => !isset($cardPaymentOption)
                    ? $this->l('Pay by online transfer or card')
                    : $this->l('Pay by online transfer'),
                'ModuleName' => $this->name,
                'Retry' => 1,
                'Logo' => $this->getPayuLogo()
            ];

            if (Configuration::get('PAYU_PAYMENT_METHODS_GRID') === '1') {
                $paymentOption['AdditionalInformation'] = $this->fetchTemplate('repaymentTransferList.tpl');
            } elseif ($retry) {
                $paymentOption['AdditionalInformation'] = '<span class="payment-name" data-pm="pbl"></span>';
            }
        } else {
            $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOption
                ->setModuleName($this->name)
                ->setAction($this->context->link->getModuleLink($this->name,'payment', ['payMethod' => Configuration::get('PAYU_PAYMENT_METHODS_GRID') === '1' ? 'transfer' : 'pbl']))
                ->setLogo($this->getPayuLogo())
                ->setCallToActionText(empty($paymentOptions)
                ? $this->l('Pay by online transfer or card')
                : $this->l('Pay by online transfer'));


            if (Configuration::get('PAYU_PAYMENT_METHODS_GRID') === '1') {
                $paymentOption->setAdditionalInformation(
                    $this->fetchTemplate('paymentTransferList17.tpl')
                )->setInputs(
                    [
                        [
                            'type' => 'hidden',
                            'name' => 'payment_id',
                            'value' => '',
                        ],
                        [
                            'type' => 'hidden',
                            'name' => 'transferGateway',
                            'value' => '',
                        ]
                    ]
                );
            } else if ($retry) {
                $paymentOption->setAdditionalInformation('<span class="payment-name" data-pm="pbl"></span>');
            }
        }

        $paymentOptions[] = $paymentOption;

        if (Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO') === '1' && $this->isPayLaterTwistoAvailable()) {
            if ($retry16) {
                $payLaterTwistoOption = [
                    'CallToActionText' => $this->l('Pay later'),
                    'AdditionalInformation' => '<span class="payment-name" data-pm="dpt"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_later_twisto_logo_small.png')
                ];
            } else {
                $payLaterTwistoOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $payLaterTwistoOption
                    ->setCallToActionText($this->l('Pay later'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_later_twisto_logo_small.png'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => 'dpt'
                        ]
                    ));
                if ($retry) {
                    $payLaterTwistoOption->setAdditionalInformation('<span class="payment-name" data-pm="dpt"></span>');
                }
            }
            $paymentOptions[] = $payLaterTwistoOption;
        }

        if ($this->isCreditAvailable($totalPrice)) {
            $this->context->smarty->assign([
                'total_price' => $totalPrice,
                'payu_installment_img' => $this->getPayuLogo('payu_installment.svg'),
                'payu_logo_img' => $this->getPayuLogo(),
                'payu_question_mark_img' => $this->getPayuLogo('question_mark.png'),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2)
            ]);
            if ($retry16) {
                $installmentOption = [
                    'CallToActionText' => $this->l('Pay online in installments'),
                    'AdditionalInformation' => $this->fetchTemplate('checkout_installment.tpl'),
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_installment.svg')
                ];
            } else {
                $installmentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $installmentOption
                    ->setCallToActionText($this->l('Pay online in installments'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_installment.svg'))
                    ->setAdditionalInformation($this->fetchTemplate('checkout_installment.tpl'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => 'ai'
                        ]
                    ));
            }
            $paymentOptions[] = $installmentOption;
        }
        return $paymentOptions;
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookPayment($params)
    {
        $paymentMethods = $this->getPaymethods(Currency::getCurrency($this->context->cart->id_currency), $params['cart']->getOrderTotal());

        $this->context->smarty->assign([
                'image' => $this->getPayuLogo(),
                'creditImage' => $this->getPayuLogo('raty_small.png'),
                'payu_logo_img' => $this->getPayuLogo(),
                'showCardPayment' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable(),
                'showWidget' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1' && $this->isCardAvailable(),
                'showBlikPayment' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT') === '1' && $this->isBlikAvailable(),
                'actionUrl' => $this->context->link->getModuleLink('payu', 'payment', ['payMethod' => 'pbl']),
                'cardActionUrl' => (Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1'
                    ? $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'card'])
                    : $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'c'])),
                'blikActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => 'blik'
                ]),
                'creditActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => 'ai'
                ]),
                'creditPayLaterTwistoActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => 'dpt'
                ]),
                'credit_available' => $this->isCreditAvailable($params['cart']->getOrderTotal()),
                'payu_later_twisto_available' => $this->isPayLaterTwistoAvailable(),
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),

                'separateBlik' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
                'separateTwisto' => Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO'),
                'separateCard' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'),
                'paymentGrid' => Configuration::get('PAYU_PAYMENT_METHODS_GRID'),
                'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
                'conditionUrl' => $this->getPayConditionUrl(),
                'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment'),
                'paymentMethods' => $paymentMethods['payByLinks'],
                'modulePath' => _PS_MODULE_DIR_ . 'payu',
                'posId' => OpenPayU_Configuration::getMerchantPosId(),
                'lang' => $this->context->language->iso_code,
                'retryPayment' => false,
                'jsSdk' => $this->getPayuUrl(Configuration::get('PAYU_SANDBOX') === '1') . 'javascript/sdk',
                'secureFormJsTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/secureFormJs.tpl',
                'payCardTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/secureForm16.tpl',
            ]
        );

        $this->setPayuNotification();

        $template = $this->fetchTemplate('/views/templates/hook/payment16.tpl');

        return $template;
    }

    public function hookDisplayPaymentEU()
    {
        $payment_options = [
            'cta_text' => $this->l('Payment by card or bank transfer via PayU'),
            'logo' => $this->getPayuLogo(),
            'action' => $this->context->link->getModuleLink('payu', 'payment')
        ];

        return $payment_options;
    }

    /**
     * @return null|string
     * @throws PrestaShopDatabaseException
     */
    public function hookAdminOrder($params)
    {
        $order = new Order((int)$params['id_order']);
        $output = '';
        $this->id_order = $order->id;
        if ($order->module !== 'payu') {
            return $output;
        }

        $updateOrderStatusMessage = '';

        $order_payment = $this->getOrderByOrderId($order->id);

        $refund_errors = [];
        $refundable = $order_payment['status'] === OpenPayuOrderStatus::STATUS_COMPLETED;

        $refund_amount = (float)$order->total_paid;

        $refund_type = Tools::getValue('payu_refund_type', 'full');
        $get_refund_amount = $refund_type === 'full' ? $refund_amount : (float)Tools::getValue('payu_refund_amount');

        if ($refundable && Tools::getValue('submitPayuRefund')) {

            if ($get_refund_amount > $order->total_paid) {
                $refund_errors[] = $this->l('The refund amount you entered is greater than paid amount.');
            } else {
                $refund = $this->payuOrderRefund($get_refund_amount, $order_payment['id_session'], $order->id);

                if (!empty($refund)) {
                    if ($refund[0] !== true) {
                        $refund_errors[] = $this->l('Refund error: ') . $refund[1];
                    }
                } else {
                    $refund_errors[] = $this->l('Refund error...');
                }
                if (empty($refund_errors)) {
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->id_employee = (int)$this->context->employee->id;
                    $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), $order->id);
                    $history->addWithemail(true, []);

                    Tools::redirectAdmin('index.php?tab=AdminOrders&id_order=' . (int)$order->id . '&vieworder' .
                        '&token=' . Tools::getAdminTokenLite('AdminOrders'));

                }
            }
        }

        $this->context->smarty->assign([
            'PAYU_ORDERS' => $this->getAllOrdersByOrderId($order->id),
            'PAYU_ORDER_ID' => $this->id_order,
            'PAYU_CANCEL_ORDER_MESSAGE' => $updateOrderStatusMessage,
            'PAYU_PAYMENT_STATUS_OPTIONS' => '',
            'PAYU_PAYMENT_STATUS' => '',
            'PAYU_PAYMENT_ACCEPT' => false,
            'IS_17' => $this->is17(),
            'SHOW_REFUND' => $refundable,
            'REFUND_FULL_AMOUNT' => $order->total_paid,
            'REFUND_ERRORS' => $refund_errors,
            'REFUND_TYPE' => $refund_type,
            'REFUND_AMOUNT' => $refund_amount
        ]);

        $show_confirm_payment_form = false;
        if (!$this->repaymentEnabled()) {
            if ($this->payu_order_id = $this->orderIsWFC($order->id)) {
                $show_confirm_payment_form = true;
                if (Tools::isSubmit('manual_change_state') && $this->payu_order_id && trim(Tools::getValue('PAYU_PAYMENT_STATUS'))) {

                    $updateOrderStatus = $this->sendPaymentUpdate(Tools::getValue('PAYU_PAYMENT_STATUS'));

                    if ($updateOrderStatus === true) {
                        $output .= $this->displayConfirmation($this->l('Update status request has been sent'));
                    } else {
                        $output .= $this->displayError($this->l('Update status request has not been completed correctly.') . ' ' . $updateOrderStatus['message']);
                    }
                }

                $this->context->smarty->assign([
                    'PAYU_PAYMENT_STATUS_OPTIONS' => $this->getPaymentAcceptanceStatusesList(),
                    'PAYU_PAYMENT_STATUS' => $order_payment['status'],
                    'PAYU_PAYMENT_ACCEPT' => $show_confirm_payment_form,
                ]);
            }
        }

        return $output . $this->fetchTemplate('/views/templates/admin/status.tpl');
    }

    /**
     * @return bool
     */
    public function repaymentEnabled()
    {
        return Configuration::get('PAYU_REPAY');
    }

    /**
     * @param int $order_id
     * @return mixed
     */
    private function orderIsWFC($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments_history
			WHERE id_order="' . addslashes($id_order) . '" and `status` in ("' . OpenPayuOrderStatus::STATUS_COMPLETED . '", "' . OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION . '", "' . OpenPayuOrderStatus::STATUS_CANCELED . '")';
        $result = Db::getInstance()->ExecuteS($sql);
        $wfc = false;
        $err = false;
        foreach ($result as $row) {
            if ($row['status'] == OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION) {
                $wfc = $row['id_session'];
            } else {
                $err = true;
            }
        }
        if ($err) {
            return false;
        } elseif (!$err && $wfc) {
            return $wfc;
        }
        return false;
    }

    /**
     * @param int $order_id
     * @return bool
     */
    private function hasPayUCompleted($id_order)
    {
        $sql = 'SELECT id_session FROM ' . _DB_PREFIX_ . 'order_payu_payments_history
			WHERE id_order="' . addslashes($id_order) . '" and `status` = "' . OpenPayuOrderStatus::STATUS_COMPLETED . '"';
        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param int $order_id
     * @return bool
     */
    private function retryPaymentHasIncorrectStatus($id_order)
    {
        $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'order_payu_payments_history
			WHERE id_order="' . addslashes($id_order) . '" and `status` in ("' . OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION . '", "' . OpenPayuOrderStatus::STATUS_COMPLETED . '", "' . OpenPayuOrderStatus::STATUS_CANCELED . '")';
        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param int $order_id
     * @param int $order_state
     * @return bool
     */
    public function hasRetryPayment($order_id, $order_state)
    {
        if ($this->repaymentEnabled()) {
            if (!$this->retryPaymentHasIncorrectStatus($order_id) && in_array($order_state, [(int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING')])) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * @return array|null
     */
    private function getProductList()
    {
        $products = $this->order->getProducts();

        if (!is_array($products) || count($products) == 0) {
            return null;
        }

        $list = [];
        foreach ($products as $product) {
            $list[] = [
                'quantity' => $product['product_quantity'],
                'name' => mb_substr($product['product_name'], 0, 255),
                'unitPrice' => $this->toAmount($product['product_price_wt'])
            ];
        }

        return $list;
    }

    /**
     * @return array|null
     */
    private function getDeliveryAddress($deliveryAddress)
    {
        if ($deliveryAddress === null) {
            return null;
        }

        $street = $deliveryAddress->address1;
        if (!empty($deliveryAddress->address2)) {
            $street .= " " . $deliveryAddress->address2;
        }
        return [
            'street' => $street,
            'postalCode' => $deliveryAddress->postcode,
            'city' => $deliveryAddress->city,
        ];
    }

    /**
     * @return array|null
     */
    private function getApplicant($parsedDeliveryAddress, $deliveryAddress)
    {
        if (!$this->order->id_customer) {
            return null;
        }
        $customer = new Customer((int)$this->order->id_customer);

        if (!$customer->email) {
            return null;
        }

        $phone = null;
        if ($deliveryAddress !== null) {
            $phone = $deliveryAddress->phone;
        }

        return [
            'email' => $customer->email,
            'firstName' => $customer->firstname,
            'lastName' => $customer->lastname,
            'language' => $this->getLanguage(),
            'phone' => $phone,
            'address' => $parsedDeliveryAddress
        ];
    }

    /**
     * @return array|null
     */
    private function getShoppingCarts($parsedDeliveryAddress)
    {
        $products = $this->getProductList();
        $shippingPrice = $this->order->total_shipping === null ? null : $this->toAmount($this->order->total_shipping);

        if (!$products && !$parsedDeliveryAddress && $shippingPrice === null) {
            return null;
        }

        return [
            [
                'shippingMethod' => [
                    'price' => $shippingPrice,
                    'address' => $parsedDeliveryAddress
                ],
                'products' => $products
            ]
        ];
    }

    /**
     * @return array|null
     */
    private function getCreditSection()
    {
        $deliveryAddress = null;
        if ($this->order->id_address_delivery) {
            $deliveryAddress = new Address((int)$this->order->id_address_delivery);
        }
        $parsedDeliveryAddress = $this->getDeliveryAddress($deliveryAddress);
        $shoppingCarts = $this->getShoppingCarts($parsedDeliveryAddress);
        $applicant = $this->getApplicant($parsedDeliveryAddress, $deliveryAddress);

        if (!$shoppingCarts && !$applicant) {
            return null;
        }

        return [
            'shoppingCarts' => $shoppingCarts,
            'applicant' => $applicant
        ];
    }

    /**
     * @param null|string $payMethod
     *
     * @return array
     * @throws Exception
     */
    public function orderCreateRequestByOrder($orderTotal, $payMethod = null, $parameters = [])
    {
        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        $currency = Currency::getCurrency($this->order->id_currency);

        if (!$this->initializeOpenPayU($currency['iso_code'])) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OPU not properly configured for currency: ' . $currency['iso_code']);
            Logger::addLog($this->displayName . ' ' . 'OPU not properly configured for currency: ' . $currency['iso_code'], 1);

            throw new \Exception('OPU not properly configured for currency: ' . $currency['iso_code']);
        }

        $cart = new Cart($this->order->id_cart);
        $customer = new Customer($cart->id_customer);
        $continueUrl = Context::getContext()->link->getPageLink('order-confirmation&ext_order=' . $this->extOrderId . '&id_cart=' . $cart->id . '&id_module=' . $this->id . '&id_order=' . $this->order->id . '&key=' . $customer->secure_key);

        $ocreq = [
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => $this->l('Order: ') . $this->order->id . ' - ' . $this->order->reference . ', ' . $this->l('Store: ') . Configuration::get('PS_SHOP_NAME'),
            'products' => [
                [
                    'quantity' => 1,
                    'name' => $this->l('Order: ') . $this->order->reference,
                    'unitPrice' => $this->toAmount($orderTotal)
                ]
            ],
            'customerIp' => $this->getIP(),
            'notifyUrl' => $this->context->link->getModuleLink('payu', 'notification'),
            'continueUrl' => $continueUrl,
            'currencyCode' => $currency['iso_code'],
            'totalAmount' => $this->toAmount($orderTotal),
            'extOrderId' => $this->extOrderId
        ];

        if ($this->getCustomer($this->order->id_customer)) {
            $ocreq['buyer'] = $this->getCustomer($this->order->id_customer);
        }

        if ($payMethod === 'ai' || $payMethod === 'dp' || $payMethod === 'dpt' || $payMethod === 'dpp') {
            $ocreq['credit'] = $this->getCreditSection();
        }
        $is_card = false;
        if ($payMethod !== null) {
            if ($payMethod === 'card' && Configuration::get('PAYU_CARD_PAYMENT_WIDGET') !== 1) {
                $is_card = true;
                $ocreq['payMethods'] = [
                    'payMethod' => [
                        'type' => 'CARD_TOKEN',
                        'value' => $parameters['cardToken']
                    ]
                ];
            } else {
                $ocreq['payMethods'] = [
                    'payMethod' => [
                        'type' => 'PBL',
                        'value' => $payMethod
                    ]
                ];
            }
        }

        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id, 'OrderCreateRequest: ');
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderCreateResponse: ');
            if ($result->getStatus() === 'SUCCESS' && $is_card) {
                return [
                    'redirectUri' => $continueUrl,
                    'orderId' => $result->getResponse()->orderId
                ];
            } elseif ($result->getStatus() === 'SUCCESS' && !$is_card || $result->getStatus() === 'WARNING_CONTINUE_3DS') {
                return [
                    'redirectUri' => $result->getResponse()->redirectUri ? urldecode($result->getResponse()->redirectUri) : $continueUrl,
                    'orderId' => $result->getResponse()->orderId
                ];
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
        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance', $this->payu_order_id);

        if (empty($this->payu_order_id)) {
            Logger::addLog($this->displayName . ' ' . 'Can not get order information - id_session is empty', 1);
        }

        $this->configureOpuByIdOrder($this->id_order);

        if ($responseNotification) {
            $response = $responseNotification;
        } else {
            $raw = OpenPayU_Order::retrieve($this->payu_order_id);
            $response = $raw->getResponse();
        }

        SimplePayuLogger::addLog(
            'order',
            __FUNCTION__,
            print_r($response, true),
            $this->payu_order_id,
            'OrderRetrieve response object: '
        );

        $payu_order = $responseNotification ? $response->order : $response->orders[0];
        $payu_properties = isset($response->properties) ? $response->properties : null;

        if ($payu_order) {
            $this->order = new Order($this->id_order);
            SimplePayuLogger::addLog(
                'notification',
                __FUNCTION__,
                'Order exists in PayU system ',
                $this->payu_order_id
            );

            $linkedOrders = [];

            foreach ($this->order->getBrother() as $linkedOrder) {
                $linkedOrders[] = $linkedOrder->id;
            }
            $this->updateOrderState($payu_order, $payu_properties, $linkedOrders);

        }
    }

    /**
     * @return string
     */
    public function getPayConditionUrl()
    {
        switch ($this->getLanguage()) {
            case 'pl':
                return self::CONDITION_PL;
            case 'cs':
                return self::CONDITION_CS;
            default:
                return self::CONDITION_EN;
        }
    }

    /**
     * @param array $currency
     * @param float $totalPrice
     *
     * @return array
     */
    public function getPaymethods($currency, $totalPrice)
    {
        try {
            $this->initializeOpenPayU($currency['iso_code']);

            $retreive = OpenPayU_Retrieve::payMethods($this->getLanguage());
            if ($retreive->getStatus() == 'SUCCESS') {
                $response = $retreive->getResponse();
                return [
                    'payByLinks' => $this->reorderPaymentMethods($response->payByLinks, $totalPrice)
                ];
            } else {
                return [
                    'error' => $retreive->getStatus() . ': ' . OpenPayU_Util::statusDesc($retreive->getStatus())
                ];
            }

        } catch (OpenPayU_Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPayuLogo($file = 'logo-payu.svg')
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
     * @param array $orders
     * @param string $status
     * @param string $payuIdOrder
     * @param string $extOrderId
     *
     * @return mixed
     */
    public function addOrdersSessionId($orders, $status, $payuIdOrder, $extOrderId)
    {
        $data = [];
        if (is_array($orders) && $orders) {
            foreach ($orders as $o) {

                $data[] = [
                    'id_order' => (int)$o['id_order'],
                    'id_cart' => (int)$o['id_cart'],
                    'id_session' => pSQL($payuIdOrder),
                    'ext_order_id' => pSQL($extOrderId),
                    'status' => pSQL($status),
                    'create_at' => date('Y-m-d H:i:s'),
                ];
                $this->insertPayuPaymentHistory($status, (int)$o['id_order'], $payuIdOrder);

                SimplePayuLogger::addLog(
                    'order',
                    __FUNCTION__,
                    'DB Insert ' . $o['id_order'],
                    $payuIdOrder
                );
            }
        }

        return Db::getInstance()->insert('order_payu_payments', $data);
    }

    /**
     * @param $id_session
     *
     * @return array
     */
    public function getOrderPaymentBySessionId($id_session)
    {
        SimplePayuLogger::addLog('notification', __FUNCTION__, 'DB query: SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments WHERE `id_session`="' . addslashes($id_session) . '"', $this->payu_order_id);
        $result = Db::getInstance()->executeS('
			SELECT * FROM `' . _DB_PREFIX_ . 'order_payu_payments`
			WHERE `id_session`="' . addslashes($id_session) . '"', true, false);

        SimplePayuLogger::addLog('notification', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'DB query result ');

        return $result;
    }

    /**
     * @param $extOrderId
     *
     * @return array | bool
     */
    public function getOrderPaymentByExtOrderId($extOrderId)
    {
        $result = Db::getInstance()->getRow('
			SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments
			WHERE ext_order_id = "' . pSQL($extOrderId) . '"
		');

        return $result ?: false;
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
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function createPayUHistoryTable()
    {
        if (Db::getInstance()->ExecuteS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'order_payu_payments_history"')) {
            return true;
        } else {
            return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_payu_payments_history` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`id_order` INT(10) UNSIGNED NOT NULL,
					`id_session` varchar(64) NOT NULL,
					`status` varchar(64) NOT NULL,
					`create_at` datetime
				)');
        }
    }

    /**
     * @param $id_order
     *
     * @return bool | array
     */
    private function getOrderByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments
        WHERE id_order = ' . (int)$id_order;

        SimplePayuLogger::addLog('notification', __FUNCTION__, $sql, $this->payu_order_id);

        return Db::getInstance()->getRow($sql, false);
    }

    private function insertPayuPaymentHistory($status, $idOrder, $payuIdOrder)
    {
        $sql_history = 'INSERT INTO ' . _DB_PREFIX_ . 'order_payu_payments_history
		SET id_order = "' . $idOrder . '", status = "' . pSQL($status) . '", create_at = NOW(), id_session="' . pSQL($payuIdOrder) . '"';
        Db::getInstance()->execute($sql_history);
    }

    /**
     * @param $status
     * @param null $previousStatus
     *
     * @return bool
     */
    private function updateOrderPaymentStatusBySessionId($status, $previousStatus = null)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_payu_payments
			SET status = "' . pSQL($status) . '", update_at = NOW()
			WHERE id_session="' . pSQL($this->payu_order_id) . '" AND status != "' . OpenPayuOrderStatus::STATUS_COMPLETED . '" AND status != "' . pSQL($status) . '"';

        if ($previousStatus) {
            $sql .= ' AND status = "' . $previousStatus . '"';
        }
        if ($status != OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION || !$this->orderIsWFC((int)$this->id_order)) {
            $this->insertPayuPaymentHistory($status, (int)$this->id_order, $this->payu_order_id);
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
     *
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

        return [
            'email' => $customer->email,
            'firstName' => $customer->firstname,
            'lastName' => $customer->lastname,
            'language' => $this->getLanguage()
        ];
    }

    private function reorderPaymentMethods($payMethods, $totalPrice)
    {
        $filteredPaymethods = [];
        foreach ($payMethods as $payMethod) {
            if ($payMethod->status !== 'ENABLED' || !$this->check_min_max($payMethod, $totalPrice * 100)) {
                continue;
            }

            if ($payMethod->value === 'c') {
                array_unshift($filteredPaymethods, $payMethod);
            } else {
                $filteredPaymethods[] = $payMethod;
            }
        }

        $paymentMethodsOrder = explode(',', str_replace(' ', '', Configuration::get('PAYU_PAYMENT_METHODS_ORDER', null, null, null, '')));

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
     * @param object $payMethod
     * @param int $total
     * @return bool
     */
    protected function check_min_max($payMethod, $total)
    {
        if (isset($payMethod->minAmount) && $total < $payMethod->minAmount) {
            return false;
        }
        if (isset($payMethod->maxAmount) && $total > $payMethod->maxAmount) {
            return false;
        }

        return true;
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

    /**
     * @param $id_order
     *
     * @return array
     */
    private function getAllOrdersByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments WHERE id_order = ' . (int)$id_order . '
			ORDER BY create_at DESC';

        SimplePayuLogger::addLog('notification', __FUNCTION__, $sql, $this->payu_order_id);
        return Db::getInstance()->executeS($sql, true, false);
    }

    /**
     * @param $id_cart
     *
     * @return array
     */
    public function getAllOrdersByCartId($id_cart)
    {

        $sql = new DbQuery();
        $sql->select('id_order, id_cart');
        $sql->from('orders');
        $sql->where('id_cart = ' . (int)($id_cart));

        return Db::getInstance()->executeS($sql, true, false);
    }

    private function configureOpuByIdOrder($idOrder)
    {
        $order = new Order($idOrder);
        $currency = Currency::getCurrency($order->id_currency);
        $this->initializeOpenPayU($currency['iso_code']);
    }

    /**
     * @param object $payu_order
     *
     * @return bool
     */
    private function updateOrderState($payu_order, $payu_properties, $linkedOrders)
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

            $ordersToChange = array_merge($linkedOrders, [$this->order->id]);
            if ($order_state_id != Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED')) {
                switch ($status) {
                    case OpenPayuOrderStatus::STATUS_CANCELED:
                        if (!$this->repaymentEnabled()) {
                            $this->setOrdersStatus($ordersToChange, Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'));
                            $this->updateOrderPaymentStatusBySessionId($status);
                        }
                        break;
                    case OpenPayuOrderStatus::STATUS_COMPLETED:
                        $this->setOrdersStatus($ordersToChange, Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'));
                        $this->addTransactionIdToPayment($this->order, $this->getTransactionId($payu_properties));
                        $this->updateOrderPaymentStatusBySessionId($status);
                        break;

                    case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
                        if ($order_state_id == Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')) {
                            OpenPayU_Order::cancel($payu_order->orderId);
                        } else {
                            $this->updateOrderPaymentStatusBySessionId($status);
                            $this->setOrdersStatus($ordersToChange, Configuration::get('PAYU_PAYMENT_STATUS_SENT'));
                            if ($this->repaymentEnabled()) {
                                if ($this->hasPayUCompleted($this->order->id)) {
                                    $this->cancelAllWFC($this->order->id);
                                } else {
                                    $status_update = [
                                        "orderId" => $payu_order->orderId,
                                        "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                                    ];
                                    OpenPayU_Order::statusUpdate($status_update);
                                }
                            }
                        }
                        break;
                }
            } else {
                if ($status === OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION) {
                    OpenPayU_Order::cancel($payu_order->orderId);
                }
            }
        }

        return false;
    }

    /**
     * @param $id_order
     */
    private function cancelAllWFC($id_order)
    {
        $sql = 'SELECT id_session FROM ' . _DB_PREFIX_ . 'order_payu_payments_history
			WHERE id_order="' . addslashes($id_order) . '" and `status` = "' . OpenPayuOrderStatus::STATUS_COMPLETED . '"';
        $completed = Db::getInstance()->getValue($sql);
        $sql = 'select distinct(id_session) as id_session from ' . _DB_PREFIX_ . 'order_payu_payments_history where id_order="' . addslashes($id_order) . '" and id_session != "' . $completed . '" and `status` = "' . OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION . '"';
        $cancel = Db::getInstance()->ExecuteS($sql);
        foreach ($cancel as $row) {
            OpenPayU_Order::cancel($row['id_session']);
        }
    }

    private function setOrdersStatus($ordersToChange, $status)
    {
        foreach ($ordersToChange as $id) {
            $history = new OrderHistory();
            $history->id_order = $id;
            $history->changeIdOrderState($status, $id);
            $history->addWithemail(true);
        }
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
            foreach ($payments as $payment) {
                $payment->transaction_id = $transactionId;
                $payment->update();
            }
        }
    }

    /**
     * @param $payu_properties
     *
     * @return string
     */
    private function getTransactionId($payu_properties)
    {
        return $payu_properties !== null ? $this->extractPaymentIdFromProperties($payu_properties) : null;
    }

    /**
     * @param array $properties
     *
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

        $list = [];
        foreach ($states as $state) {
            $list[] = [
                'id' => $state['id_order_state'],
                'name' => $state['name']
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    private function getPaymentAcceptanceStatusesList()
    {
        return [
            ['id' => OpenPayuOrderStatus::STATUS_COMPLETED, 'name' => $this->l('Accept the payment')],
            ['id' => OpenPayuOrderStatus::STATUS_CANCELED, 'name' => $this->l('Reject the payment')]
        ];
    }

    /**
     * @param $value
     *
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
            $this->registerHook('ActionGetExtraMailTemplateVars');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('displayPaymentEU') && $this->registerHook('payment');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        $extOrder = Tools::getValue('ext_order');

        if ($extOrder) {
            $order_payment = $this->getOrderPaymentByExtOrderId($extOrder);

            if (isset($params['order'])) {
                $order = $params['order'];
            } elseif (isset($params['objOrder'])) {
                $order = $params['objOrder'];
            } else {
                return '';
            }

            if ($order->id === (int)$order_payment['id_order'] && $order_payment['status'] !== OpenPayuOrderStatus::STATUS_COMPLETED) {
                $this->id_order = $order->id;
                $this->payu_order_id = $order_payment['id_session'];
                $this->updateOrderData();
            }
        }

        $this->context->smarty->assign([
            'payuLogo' => $this->getPayuLogo()
        ]);

        return $this->fetchTemplate('/views/templates/hook/paymentReturn.tpl');
    }

    public function hookDisplayCheckoutSubtotalDetails($params)
    {
        if ($this->isCreditAvailable($params['cart']->getOrderTotal())
            && Configuration::get('PAYU_PROMOTE_CREDIT_CART') === '1') {
            $this->context->smarty->assign([
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2)
            ]);
            return $this->display(__FILE__, 'cart-detailed-totals.tpl');
        }
    }

    public function hookDisplayCheckoutSummaryTop($params)
    {
        if (Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY') === '1' &&
            $this->isCreditAvailable($params['cart']->getOrderTotal())) {
            $this->context->smarty->assign([
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2)
            ]);
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
                if ($priceWithDot >= self::PAYU_MIN_CREDIT_AMOUNT &&
                    $priceWithDot <= self::PAYU_MAX_CREDIT_AMOUNT) {
                    $creditAvailable = true;
                }

                if ($creditAvailable) {
                    $this->context->smarty->assign([
                        'product_price' => $price,
                        'product_id' => $productId,
                        'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                        'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2)
                    ]);
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
            if ($creditAvailable && (
                    ($params['type'] === 'weight' && $current_controller === 'index') ||
                    ($params['type'] === 'after_price' && $current_controller === 'product') ||
                    ($params['type'] === 'weight' && $current_controller === 'category') ||
                    ($params['type'] === 'weight' && $current_controller === 'search')
                )) {
                $this->context->smarty->assign([
                    'product_price' => $product['price_amount'],
                    'product_id' => $product['id_product'],
                    'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                    'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2)
                ]);
                return $this->display(__FILE__, 'product.tpl', $this->getCacheId($product['price_amount'] . $product['id_product']));
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
     *
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
                    $status_update = [
                        "orderId" => $this->payu_order_id,
                        "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                    ];
                    $result = OpenPayU_Order::statusUpdate($status_update);
                }
            } catch (OpenPayU_Exception $e) {
                return [
                    'message' => $e->getMessage()
                ];
            }

            if ($result->getStatus() == 'SUCCESS') {
                return true;
            } else {
                return [
                    'message' => $result->getError() . ' ' . $result->getMessage()
                ];
            }
        }
        return [
            'message' => $this->l('Order status update hasn\'t been sent')
        ];
    }

    /**
     * @param string $state
     * @param array $names
     *
     * @return bool
     */
    public function addNewOrderState($state, $names)
    {
        if (!(Validate::isInt(Configuration::get($state)) && Validate::isLoadedObject($order_state = new OrderState(Configuration::get($state))))) {
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

            if ($refund->getStatus() === 'SUCCESS') {
                return [true];
            } else {
                Logger::addLog($this->displayName . ' Order Refund error: ', 1);
                return [false, 'Status code: ' . $refund->getStatus()];
            }

        } catch (OpenPayU_Exception_Request $e) {
            $response = $e->getOriginalResponse()->getResponse()->status;
            Logger::addLog($this->displayName . ' Order Refund error: ' . $response->codeLiteral . ' [' . $response->code . ']', 1);
            return [false, $response->codeLiteral . ' [' . $response->code . '] - <a target="_blank" href="http://developers.payu.com/pl/restapi.html#refunds">developers.payu.com</a>'];
        } catch (OpenPayU_Exception $e) {
            Logger::addLog($this->displayName . ' Order Refund error: ' . $e->getMessage(), 1);
            return [false, $e->getMessage()];
        }

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
        if (!$this->is17()) {
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
    public function is17()
    {
        return !version_compare(_PS_VERSION_, '1.7', 'lt');
    }

    /**
     * @param $amount
     *
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
     * @return bool
     */
    private function isCardAvailable()
    {
        return Configuration::get('PAYU_PAYMENT_METHODS_GRID') !== '1'
            || PayMethodsCache::isPaytypeAvailable('c',
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion(), true);
    }

    /**
     * @return bool
     */
    private function isBlikAvailable()
    {
        return Configuration::get('PAYU_PAYMENT_METHODS_GRID') !== '1'
            || PayMethodsCache::isPaytypeAvailable('blik',
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion(), true);
    }

    /**
     * @return bool
     */
    private function isPayLaterTwistoAvailable()
    {
        return (Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO') === '1'
                || Configuration::get('PAYU_PROMOTE_CREDIT') === '1')
            && PayMethodsCache::isDelayedPaymentTwistoAvailable(
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getVersion());
    }

    private function setPayuNotification()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['payuNotifications'])) {
            $payuNotifications = json_decode($_SESSION['payuNotifications'], true);
            unset($_SESSION['payuNotifications']);
        } elseif (isset($_COOKIE['payuNotifications'])) {
            $payuNotifications = json_decode($_COOKIE['payuNotifications'], true);
            unset($_COOKIE['payuNotifications']);
        }

        if (isset($payuNotifications)) {
            $this->smarty->assign([
                'payuNotifications' => $payuNotifications
            ]);
        }
    }
}
