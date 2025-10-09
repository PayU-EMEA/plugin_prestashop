<?php
/**
 * PayU module
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2025 PayU
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/PayUSDKInitializer.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayMethodsCache/PayMethodsCache.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayMethods/CreditPaymentMethod.php');

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

	/** @var int */
	public $is_eu_compatible;

    public function __construct()
    {
        $this->name = 'payu';
        $this->displayName = 'PayU';
        $this->tab = 'payments_gateways';
        $this->version = '3.3.3';
        $this->author = 'PayU';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
			'min' => '1.6.0',
			'max' => _PS_VERSION_
        ];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        parent::__construct();

		$this->displayName = $this->l('PayU');
        $this->description = $this->l('Accepts payments by PayU');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');
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
            Configuration::updateValue('PAYU_LOGGER', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', 0) &&
            Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', '') &&
            Configuration::updateValue('PAYU_SEPARATE_INSTALLMENTS', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', 1) &&
            Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', 1) &&
            Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_TWISTO', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_TWISTO_SLICE', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_PRAGMA_PAY', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_KLARNA', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_PAYPO', 0) &&
            Configuration::updateValue('PAYU_SEPARATE_BLIK_PAYMENT', 0) &&
            Configuration::updateValue('PAYU_PAYMENT_METHODS_GRID', 0) &&
            Configuration::updateValue('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES', '')
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
            !Configuration::deleteByName('PAYU_LOGGER') ||
            !Configuration::deleteByName('PAYU_SEPARATE_CARD_PAYMENT') ||
            !Configuration::deleteByName('PAYU_CARD_PAYMENT_WIDGET') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_ORDER') ||
            !Configuration::deleteByName('PAYU_SEPARATE_INSTALLMENTS') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_CART') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_SUMMARY') ||
            !Configuration::deleteByName('PAYU_PROMOTE_CREDIT_PRODUCT') ||
            !Configuration::deleteByName('PAYU_SEPARATE_PAY_LATER_TWISTO') ||
            !Configuration::deleteByName('PAYU_SEPARATE_TWISTO_SLICE') ||
            !Configuration::deleteByName('PAYU_SEPARATE_PRAGMA_PAY') ||
            !Configuration::deleteByName('PAYU_SEPARATE_PAY_LATER_KLARNA') ||
            !Configuration::deleteByName('PAYU_SEPARATE_PAY_LATER_PAYPO') ||
            !Configuration::deleteByName('PAYU_SEPARATE_BLIK_PAYMENT') ||
            !Configuration::deleteByName('PAYU_PAYMENT_METHODS_GRID', 0) ||
            !Configuration::deleteByName('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES')
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
                !Configuration::updateValue('PAYU_LOGGER', (Tools::getValue('PAYU_LOGGER') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_CARD_PAYMENT', (Tools::getValue('PAYU_SEPARATE_CARD_PAYMENT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_BLIK_PAYMENT', (Tools::getValue('PAYU_SEPARATE_BLIK_PAYMENT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_CARD_PAYMENT_WIDGET', (Tools::getValue('PAYU_CARD_PAYMENT_WIDGET') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_ORDER', Tools::getValue('PAYU_PAYMENT_METHODS_ORDER')) ||
                !Configuration::updateValue('PAYU_SEPARATE_INSTALLMENTS', (Tools::getValue('PAYU_SEPARATE_INSTALLMENTS') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_CART', (Tools::getValue('PAYU_PROMOTE_CREDIT_CART') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_SUMMARY', (Tools::getValue('PAYU_PROMOTE_CREDIT_SUMMARY') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PROMOTE_CREDIT_PRODUCT', (Tools::getValue('PAYU_PROMOTE_CREDIT_PRODUCT') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_TWISTO', (Tools::getValue('PAYU_SEPARATE_PAY_LATER_TWISTO') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_TWISTO_SLICE', (Tools::getValue('PAYU_SEPARATE_TWISTO_SLICE') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_PRAGMA_PAY', (Tools::getValue('PAYU_SEPARATE_PRAGMA_PAY') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_KLARNA', (Tools::getValue('PAYU_SEPARATE_PAY_LATER_KLARNA') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_SEPARATE_PAY_LATER_PAYPO', (Tools::getValue('PAYU_SEPARATE_PAY_LATER_PAYPO') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_PAYMENT_METHODS_GRID', (Tools::getValue('PAYU_PAYMENT_METHODS_GRID') ? 1 : 0)) ||
                !Configuration::updateValue('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES', Tools::getValue('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES'))
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

        $output .= $this->fetchTemplate('views/templates/admin/info.tpl');
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
                    [
                        'type' => 'switch',
                        'label' => $this->l('Save logs'),
                        'name' => 'PAYU_LOGGER',
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

        $form['credit_payments'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Credit payments'),
                    'icon' => 'icon-tag'
                ],
                'input' => array_merge([
                    [
                        'type' => 'switch',
                        'label' => $this->l('Separate installments'),
                        'desc' => $this->l('Shows separate installments payment method'),
                        'name' => 'PAYU_SEPARATE_INSTALLMENTS',
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
                        'label' => $this->l('Separate pay later Klarna'),
                        'desc' => $this->l('Shows separate Klarna payment method'),
                        'name' => 'PAYU_SEPARATE_PAY_LATER_KLARNA',
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
                        'label' => $this->l('Separate pay later PayPo'),
                        'desc' => $this->l('Shows separate PayPo payment method'),
                        'name' => 'PAYU_SEPARATE_PAY_LATER_PAYPO',
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
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Separate Twisto pay in 3'),
                        'desc' => $this->l('Shows separate Twisto pay in 3 payment method'),
                        'name' => 'PAYU_SEPARATE_TWISTO_SLICE',
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
                        'label' => $this->l('Separate PragmaPay'),
                        'desc' => $this->l('Shows separate PragmaPay payment method'),
                        'name' => 'PAYU_SEPARATE_PRAGMA_PAY',
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
                            'label' => $this->l('Show credit payments widget on cart'),
                            'desc' => $this->l('Promotes credit payments on cart'),
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
                            'label' => $this->l('Show credit payments widget on summary'),
                            'desc' => $this->l('Promotes credit payments on summary'),
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
                            'label' => $this->l('Show credit payments widget on product'),
                            'desc' => $this->l('Promotes credit payments on product'),
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
                        [
                            'type' => 'text',
                            'label' => $this->l('Exclude credit payment methods from widget'),
                            'desc' => $this->l('Excludes the given credit payment methods from the credit payment widget. The value must be a comma-separated list of') . ' <a href="https://developers.payu.com/europe/pl/docs/get-started/integration-overview/references/#installments-and-pay-later" target="_blank" rel="nofollow">'
                                . $this->l('credit payment method codes') . '</a>, ' . $this->l('for example') . ': dpt,dpkl,dpp.',
                            'name' => 'PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES'
                        ]
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
                            'query' => $this->getStatesList(true),
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
            'PAYU_LOGGER' => Configuration::get('PAYU_LOGGER'),
            'PAYU_SEPARATE_CARD_PAYMENT' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT'),
            'PAYU_SEPARATE_BLIK_PAYMENT' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
            'PAYU_CARD_PAYMENT_WIDGET' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET'),
            'PAYU_PAYMENT_METHODS_ORDER' => Configuration::get('PAYU_PAYMENT_METHODS_ORDER'),
            'PAYU_SEPARATE_INSTALLMENTS' => Configuration::get('PAYU_SEPARATE_INSTALLMENTS'),
            'PAYU_PROMOTE_CREDIT_CART' => Configuration::get('PAYU_PROMOTE_CREDIT_CART'),
            'PAYU_PROMOTE_CREDIT_SUMMARY' => Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY'),
            'PAYU_PROMOTE_CREDIT_PRODUCT' => Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT'),
            'PAYU_SEPARATE_PAY_LATER_TWISTO' => Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO'),
            'PAYU_SEPARATE_TWISTO_SLICE' => Configuration::get('PAYU_SEPARATE_TWISTO_SLICE'),
            'PAYU_SEPARATE_PRAGMA_PAY' => Configuration::get('PAYU_SEPARATE_PRAGMA_PAY'),
            'PAYU_SEPARATE_PAY_LATER_KLARNA' => Configuration::get('PAYU_SEPARATE_PAY_LATER_KLARNA'),
            'PAYU_SEPARATE_PAY_LATER_PAYPO' => Configuration::get('PAYU_SEPARATE_PAY_LATER_PAYPO'),
            'PAYU_PAYMENT_METHODS_GRID' => Configuration::get('PAYU_PAYMENT_METHODS_GRID'),
            'PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES' => Configuration::get('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES')
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
        $output = '<link type="text/css" rel="stylesheet" href="' . _MODULE_DIR_ . $this->name . '/css/payu.css" />';

        return $output;
    }

    public function hookActionGetExtraMailTemplateVars(array &$params)
    {
        if (isset($params['template_vars']['{payment}'])
            && $this->displayName === substr($params['template_vars']['{payment}'],0, strlen($this->displayName))
            && $this->repaymentEnabled()
            && $params['template'] == 'order_conf'
        ) {
            if ($this->is17() && isset($params['template_vars']['{id_order}'])) {
                $id_order = $params['template_vars']['{id_order}'];
            } else {
                $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference="' . $params['template_vars']['{order_name}'] . '" limit 1';
                $result = Db::getInstance()->ExecuteS($sql);
                $id_order = $result[0]['id_order'];
            }

            $order = new Order($id_order);
            $customer = new Customer($order->id_customer);

            if ($customer->is_guest) {
                $urlParams = [
                    'email' => $customer->email,
                    'order_reference' => $params['template_vars']['{order_name}']
                ];
                $url = $this->context->link->getPageLink('guest-tracking', null, null, $urlParams);

            } else {
                $urlParams = [
                    'id_order' => $id_order
                ];
                $url = $this->context->link->getPageLink('order-detail', null, null, $urlParams);
            }

            $params['extra_template_vars']['{payment}'] = $params['template_vars']['{payment}'] . ', <a href="' . $url . '#repayment">' . $this->l('Repay by PayU') . '</a>';

        }
    }

    public function hookHeader()
    {
        $controller = Context::getContext()->controller->php_self;

        if ($this->isCreditWidgetEnabled()) {
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
            $controller === 'guest-tracking' ||
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
                    'conditionUrl' => $this->getPayConditionUrl(),
                    'gateways' => $this->hookPaymentOptions($retry_params, true)
                ]
            );

            return $this->fetchTemplate($this->is17() ? 'retryPayment17.tpl' : 'retryPayment.tpl');
        }
    }

    /**
     *
     * @param bool $retry
     * @param bool $retry16
     * @param float $totalPrice
     *
     * @return array
     */
    private function getCreditPaymentOptions($retry, $retry16, $totalPrice)
    {
        $creditPaymentOptions = [];

        $availablePayLaterKlarna = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_KLARNA_GROUP, $totalPrice);
        $separateKlarna = false;
        if ($availablePayLaterKlarna != null && Configuration::get('PAYU_SEPARATE_PAY_LATER_KLARNA') === '1') {
            if ($retry16) {
                $payLaterKlarnaOption = [
                    'CallToActionText' => $this->l('Pay later'),
                    'AdditionalInformation' => '<span class="payment-name" data-pm="' . $availablePayLaterKlarna . '"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_later_klarna_logo.svg')
                ];
            } else {
                $payLaterKlarnaOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $payLaterKlarnaOption
                    ->setCallToActionText($this->l('Pay later'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_later_klarna_logo.svg'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => $availablePayLaterKlarna
                        ]
                    ));
                if ($retry) {
                    $payLaterKlarnaOption->setAdditionalInformation('<span class="payment-name" data-pm="' . $availablePayLaterKlarna . '"></span>');
                }
            }
            $creditPaymentOptions[] = $payLaterKlarnaOption;
            $separateKlarna = true;
        }
        $this->context->smarty->assign([
            'separateKlarna' => $separateKlarna
        ]);

        $availablePayLaterPaypo = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_PAYPO_GROUP, $totalPrice);
        $separatePaypo = false;
        if ($availablePayLaterPaypo != null && Configuration::get('PAYU_SEPARATE_PAY_LATER_PAYPO') === '1') {
            if ($retry16) {
                $payLaterPaypoOption = [
                    'CallToActionText' => $this->l('Pay later'),
                    'AdditionalInformation' => '<span class="payment-name" data-pm="' . $availablePayLaterPaypo . '"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_later_paypo_logo.svg')
                ];
            } else {
                $payLaterPaypoOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $payLaterPaypoOption
                    ->setCallToActionText($this->l('Pay later'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_later_paypo_logo.svg'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => $availablePayLaterPaypo
                        ]
                    ));
                if ($retry) {
                    $payLaterPaypoOption->setAdditionalInformation('<span class="payment-name" data-pm="' . $availablePayLaterPaypo . '"></span>');
                }
            }
            $creditPaymentOptions[] = $payLaterPaypoOption;
            $separatePaypo = true;
        }
        $this->context->smarty->assign([
            'separatePaypo' => $separatePaypo
        ]);

        $availablePayLaterTwisto = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_TWISTO_GROUP, $totalPrice);
        $separateTwisto = false;
        if ($availablePayLaterTwisto != null && Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO') === '1') {
            if ($retry16) {
                $payLaterTwistoOption = [
                    'CallToActionText' => $this->l('Pay later'),
                    'AdditionalInformation' => '<span class="payment-name" data-pm="' . $availablePayLaterTwisto . '"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_later_twisto_logo.svg')
                ];
            } else {
                $payLaterTwistoOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $payLaterTwistoOption
                    ->setCallToActionText($this->l('Pay later'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_later_twisto_logo.svg'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => $availablePayLaterTwisto
                        ]
                    ));
                if ($retry) {
                    $payLaterTwistoOption->setAdditionalInformation('<span class="payment-name" data-pm="' . $availablePayLaterTwisto . '"></span>');
                }
            }
            $creditPaymentOptions[] = $payLaterTwistoOption;
            $separateTwisto = true;
        }
        $this->context->smarty->assign([
            'separateTwisto' => $separateTwisto
        ]);

        if ($this->isAvailableSeparateTwistoSlice($totalPrice)) {
            if ($retry16) {
                $twistoSliceOption = [
                    'CallToActionText' => $this->l('Pay with Twisto pay in 3'),
                    'AdditionalInformation' => '<span class="payment-name" data-pm="dpts"></span>',
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_twisto_pay_in_3.svg')
                ];
            } else {
                $twistoSliceOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $twistoSliceOption
                    ->setCallToActionText($this->l('Pay with Twisto pay in 3'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_twisto_pay_in_3.svg'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => CreditPaymentMethod::INSTALLMENT_TWISTO_SLICE
                        ]
                    ));
                if ($retry) {
                    $twistoSliceOption->setAdditionalInformation('<span class="payment-name" data-pm="dpts"></span>');
                }
            }
            $creditPaymentOptions[] = $twistoSliceOption;
        }

        if ($this->isAvailableSeparatePragmaPay($totalPrice)) {
            if ($retry16) {
                $pragmaPayOption = [
                    'CallToActionText' => $this->l('Pay with PragmaPay'),
                    'AdditionalInformation' => $this->fetchTemplate('checkout_pragma_pay.tpl'),
                    'ModuleName' => $this->name,
                    'Logo' => $this->getPayuLogo('payu_pragma_pay.svg')
                ];
            } else {
                $pragmaPayOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $pragmaPayOption
                    ->setCallToActionText($this->l('Pay with PragmaPay'))
                    ->setModuleName($this->name)
                    ->setLogo($this->getPayuLogo('payu_pragma_pay.svg'))
                    ->setAdditionalInformation($this->fetchTemplate('checkout_pragma_pay.tpl'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                        [
                            'payMethod' => CreditPaymentMethod::PRAGMA_PAY
                        ]
                    ));
            }
            $creditPaymentOptions[] = $pragmaPayOption;
        }

        if ($this->isAvailableSeparateInstallments($totalPrice)) {
            $this->context->smarty->assign([
                'total_price' => $totalPrice,
                'payu_installment_img' => $this->getPayuLogo('payu_installment.svg'),
                'payu_logo_img' => $this->getPayuLogo(),
                'payu_question_mark_img' => $this->getPayuLogo('question_mark.png')
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
                            'payMethod' => CreditPaymentMethod::INSTALLMENT
                        ]
                    ));
            }
            $creditPaymentOptions[] = $installmentOption;
        }

        return $creditPaymentOptions;
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

        // credit payment options definition must stay on top, because it assigns smarty variables,
        // which are used by paymentTransferList17.tpl
        $creditPaymentOptions = $this->getCreditPaymentOptions($retry, $retry16, $totalPrice);

        $this->smarty->assign([
            'conditionTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/conditions17.tpl',
            'conditionUrl' => $this->getPayConditionUrl(),
            'payuPayAction' => $this->context->link->getModuleLink('payu', 'payment'),
            'paymentMethods' => $paymentMethods['payByLinks'],
            'separateBlik' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
            'separateInstallments' => Configuration::get('PAYU_SEPARATE_INSTALLMENTS'),
            'separateTwistoSlice' => Configuration::get('PAYU_SEPARATE_TWISTO_SLICE'),
            'separatePragmaPay' => Configuration::get('PAYU_SEPARATE_PRAGMA_PAY'),
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
        if (Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable($totalPrice)) {
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

        if (Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT') === '1' && $this->isBlikAvailable($totalPrice)) {
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

        return array_merge($paymentOptions, $creditPaymentOptions);
    }

    /**
     * @param float $totalPrice
     *
     * @return void
     */
    private function assignCreditPaymentVariablesForPaymentHook($totalPrice)
    {
        $availablePayLaterTwisto = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_TWISTO_GROUP, $totalPrice);
        if ($availablePayLaterTwisto != null) {
            $this->context->smarty->assign([
                'separateTwisto' => Configuration::get('PAYU_SEPARATE_PAY_LATER_TWISTO') === '1',
                'creditPayLaterTwistoActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => $availablePayLaterTwisto
                ]),
            ]);
        } else {
            $this->context->smarty->assign([
                'separateTwisto' => false
            ]);
        }

        $availablePayLaterKlarna = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_KLARNA_GROUP, $totalPrice);
        if ($availablePayLaterKlarna != null) {
            $this->context->smarty->assign([
                'separateKlarna' => Configuration::get('PAYU_SEPARATE_PAY_LATER_KLARNA') === '1',
                'creditPayLaterKlarnaActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => $availablePayLaterKlarna
                ]),
            ]);
        } else {
            $this->context->smarty->assign([
                'separateKlarna' => false
            ]);
        }

        $availablePayLaterPaypo = $this->findAvailableCreditPayMethod(CreditPaymentMethod::DELAYED_PAYMENT_PAYPO_GROUP, $totalPrice);
        if ($availablePayLaterPaypo != null) {
            $this->context->smarty->assign([
                'separatePaypo' => Configuration::get('PAYU_SEPARATE_PAY_LATER_PAYPO') === '1',
                'creditPayLaterPaypoActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => $availablePayLaterPaypo
                ]),
            ]);
        } else {
            $this->context->smarty->assign([
                'separatePaypo' => false
            ]);
        }

        $this->context->smarty->assign([
            'creditActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                'payMethod' => CreditPaymentMethod::INSTALLMENT
            ]),
            'creditPayLaterTwistoSliceActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                'payMethod' => CreditPaymentMethod::INSTALLMENT_TWISTO_SLICE
            ]),
            'creditPragmaPayActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                'payMethod' => CreditPaymentMethod::PRAGMA_PAY
            ]),
            'separateInstallments' => $this->isAvailableSeparateInstallments($totalPrice),
            'separateTwistoSlice' => $this->isAvailableSeparateTwistoSlice($totalPrice),
            'separatePragmaPay' => $this->isAvailableSeparatePragmaPay($totalPrice),
        ]);

        if ($this->isAnyCreditPaytypeEnabled()) {
            $this->context->smarty->assign([
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),
                'credit_widget_currency_code' => $this->getCurrencyIsoCodeForCreditWidget(),
                'credit_widget_lang' => $this->getLanguage(),
                'credit_widget_excluded_paytypes' => $this->getCreditWidgetExcludedPaytypes()
            ]);
        }
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookPayment($params)
    {
        $paymentMethods = $this->getPaymethods(Currency::getCurrency($this->context->cart->id_currency), $params['cart']->getOrderTotal());

        $this->assignCreditPaymentVariablesForPaymentHook($params['cart']->getOrderTotal());

        $this->context->smarty->assign([
                'image' => $this->getPayuLogo(),
                'payu_logo_img' => $this->getPayuLogo(),
                'showCardPayment' => Configuration::get('PAYU_SEPARATE_CARD_PAYMENT') === '1' && $this->isCardAvailable($params['cart']->getOrderTotal()),
                'showWidget' => Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1' && $this->isCardAvailable($params['cart']->getOrderTotal()),
                'showBlikPayment' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT') === '1' && $this->isBlikAvailable($params['cart']->getOrderTotal()),
                'actionUrl' => $this->context->link->getModuleLink('payu', 'payment', ['payMethod' => 'pbl']),
                'cardActionUrl' => (Configuration::get('PAYU_CARD_PAYMENT_WIDGET') === '1'
                    ? $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'card'])
                    : $this->context->link->getModuleLink($this->name, 'payment', ['payMethod' => 'c'])),
                'blikActionUrl' => $this->context->link->getModuleLink('payu', 'payment', [
                    'payMethod' => 'blik'
                ]),
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'separateBlik' => Configuration::get('PAYU_SEPARATE_BLIK_PAYMENT'),
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
                'payCardTemplate' => _PS_MODULE_DIR_ . 'payu/views/templates/front/secureForm16.tpl'
            ]
        );

        $this->setPayuNotification();

        $template = $this->fetchTemplate('views/templates/hook/payment16.tpl');

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
     */
    public function hookAdminOrder($params)
    {
        $output = '';

        try {
            $order = new Order((int)$params['id_order']);
        } catch (PrestaShopException $e) {
            return $output;
        }

        $this->id_order = $order->id;
        if ($order->module !== 'payu') {
            return $output;
        }

        $updateOrderStatusMessage = '';

        $order_payment = $this->getOrderByOrderId($order->id);

        $refund_errors = [];
        $refundable = $order_payment['status'] === OpenPayuOrderStatus::STATUS_COMPLETED;

        $refund_amount = $order->total_paid;
        $refund_type = Tools::getValue('payu_refund_type', 'full');

        if ($refundable && Tools::getValue('submitPayuRefund')) {


            if ($refund_type === 'full') {
                $get_refund_amount = $refund_amount;
            } else {
                $val = str_replace(",", ".", Tools::getValue('payu_refund_amount', ''));
                $isNumber = preg_match('/^[0-9]+(\\.[0-9]+)?$/', $val);
                $get_refund_amount = $isNumber ? (float)$val : null;
            }

            if ($get_refund_amount === null) {
                $refund_errors[] = $this->l('The refund amount you entered is invalid.');
            } else if ($get_refund_amount > $order->total_paid) {
                $refund_errors[] = $this->l('The refund amount you entered is greater than paid amount.');
            } else {
                $refund = $this->payuOrderRefund($get_refund_amount, $order_payment['id_session'], $order);

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

                    Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => (int)$order->id]));
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

        if (!$this->repaymentEnabled()) {
            if ($this->payu_order_id = $this->orderIsWFC($order->id)) {
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
                    'PAYU_PAYMENT_ACCEPT' => true,
                ]);
            }
        }

        return $output . $this->fetchTemplate('views/templates/admin/status.tpl');
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
     * @param bool $withDiscountAndShipment
     * @return array|null
     */
    private function getProductList($withDiscountAndShipment = false)
    {
        $products = $this->order->getProducts();
        if (!is_array($products) || count($products) == 0) {
            return null;
        }

        $list = [];
        $i = 0;

        foreach ($products as $product) {
            $list[$i] = [
                'quantity' => $product['product_quantity'],
                'name' => mb_substr($product['product_name'], 0, 255),
                'unitPrice' => $this->toAmount($product['product_price_wt'])
            ];

            if ($product['is_virtual']) {
                $list[$i]['virtual'] = true;
            }

            $i++;
        }

        if ($withDiscountAndShipment) {
            if (!$this->order->isVirtual()) {
                $shippings = $this->order->getShipping();

                foreach ($shippings as $shipping) {
                    $list[] = [
                        'name' => mb_substr('Shipment' . ' [' . $shipping['carrier_name'] . ']', 0, 255),
                        'unitPrice' => $this->toAmount(Tools::ps_round($shipping['shipping_cost_tax_incl'], 2)),
                        'quantity' => 1
                    ];
                }
            }

            $rules = $this->order->getCartRules();

            foreach ($rules as $rule) {
                $list[] = [
                    'name' => mb_substr('Discount' . ' [' . $rule['name'] . ']', 0, 255),
                    'unitPrice' => $this->toAmount(Tools::ps_round($rule['value'],2) * -1),
                    'quantity' => 1
                ];
            }
        }

        return $list;
    }

    /**
     * @param Order $order
     * @param array $ocrData
     *
     * @return array | false
     */
    private function getThreeDsAuthentication($order, $ocrData)
    {
        if (!isset($ocrData['payMethods'])
            || $ocrData['payMethods']['payMethod']['type'] === 'CARD_TOKEN'
            || $ocrData['payMethods']['payMethod']['value'] === 'c'
            || $ocrData['payMethods']['payMethod']['value'] === 'ap'
            || $ocrData['payMethods']['payMethod']['value'] === 'jp'
            || $ocrData['payMethods']['payMethod']['value'] === 'ma'
            || $ocrData['payMethods']['payMethod']['value'] === 'vc'
        ) {

            $billingData = new Address($order->id_address_invoice);
            $threeDsAuthentication = false;

            $name = $billingData->firstname . ' ' . $billingData->lastname;
            $address = $billingData->address1 . ($billingData->address2 ? ' ' . $billingData->address2 : '');
            $postalCode = $billingData->postcode;
            $city = $billingData->city;
            if ($billingData->id_country) {
                $country = new Country($billingData->id_country);
                $countryCode = $country->iso_code;
            } else {
                $countryCode = '';
            }

            $isBillingAddress = !empty($address) || !empty($postalCode) || !empty($city) || (!empty($countryCode) && strlen($countryCode) === 2);

            if (!empty($name) || $isBillingAddress) {
                $threeDsAuthentication = [
                    'cardholder' => []
                ];

                if (!empty($name)) {
                    $threeDsAuthentication['cardholder']['name'] = mb_substr($name, 0, 50);
                }

                if ($isBillingAddress) {
                    $threeDsAuthentication['cardholder']['billingAddress'] = [];
                }

                if (!empty($countryCode) && strlen($countryCode) === 2) {
                    $threeDsAuthentication['cardholder']['billingAddress']['countryCode'] = $countryCode;
                }

                if (!empty($address)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['street'] = mb_substr($address, 0, 50);
                }

                if (!empty($city)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['city'] = mb_substr($city, 0, 50);
                }

                if (!empty($postalCode)) {
                    $threeDsAuthentication['cardholder']['billingAddress']['postalCode'] = mb_substr($postalCode, 0, 16);
                }
            }

            $payuBrowser = Tools::getValue('payuBrowser');

            if (isset($ocrData['payMethods']['payMethod']['type']) && $ocrData['payMethods']['payMethod']['type'] === 'CARD_TOKEN'
                && isset($payuBrowser)
                && is_array($payuBrowser)
            ) {
                $possibleBrowserData = ['screenWidth', 'javaEnabled', 'timezoneOffset', 'screenHeight', 'userAgent', 'colorDepth', 'language'];
                $browserData = [
                    'requestIP' => Tools::getRemoteAddr()
                ];

                foreach ($possibleBrowserData as $bd) {
                    $browserData[$bd] = isset($payuBrowser[$bd]) ? $payuBrowser[$bd] : '';
                }

                if (empty($browserData['userAgent'])) {
                    if ($_SERVER['HTTP_USER_AGENT']) {
                        $browserData['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                    }
                }

                $threeDsAuthentication['browser'] = $browserData;
            }

            return $threeDsAuthentication;
        }

        return false;
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
        $customer = new Customer($this->order->id_customer);

        $params = [
            'ext_order' => $this->extOrderId,
            'id_cart' => $cart->id,
            'id_module' => $this->id,
            'id_order' => $this->order->id,
            'key' => $customer->secure_key
        ];
        $continueUrl = $this->context->link->getPageLink('order-confirmation', null, null, $params);

        $ocreq = [
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => $this->l('Order:') . ' ' . $this->order->id . ' - ' . $this->order->reference . ', ' . $this->l('Store:') . ' ' . Configuration::get('PS_SHOP_NAME'),
            'customerIp' => Tools::getRemoteAddr(),
            'notifyUrl' => $this->context->link->getModuleLink('payu', 'notification'),
            'continueUrl' => $continueUrl,
            'currencyCode' => $currency['iso_code'],
            'totalAmount' => $this->toAmount($orderTotal),
            'extOrderId' => $this->extOrderId,
            'buyer' => $this->getBuyer($customer, $this->order),
        ];
        $products = $this->getProductList(true);

        if ($products) {
            $ocreq['products'] = $products;
        } else {
            $ocreq['products'] = [
                [
                    'quantity' => 1,
                    'name' => mb_substr($this->l('Order:') . ' ' . $this->order->reference, 0, 255),
                    'unitPrice' => $this->toAmount($orderTotal)
                ]
            ];
        }

        if (in_array($payMethod, CreditPaymentMethod::getAll())) {
            $ocreq['credit'] = $this->getCreditSection();
        }

        if ($payMethod !== null) {
            if ($payMethod === 'card' && Configuration::get('PAYU_CARD_PAYMENT_WIDGET') !== 1) {
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

        $threeDsAuthentication = $this->getThreeDsAuthentication($this->order, $ocreq);

        if ($threeDsAuthentication !== false) {
            $ocreq['threeDsAuthentication'] = $threeDsAuthentication;
        }

        try {
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($ocreq, true), $this->payu_order_id, 'OrderCreateRequest: ');
            $result = OpenPayU_Order::create($ocreq);
            SimplePayuLogger::addLog('order', __FUNCTION__, print_r($result, true), $this->payu_order_id, 'OrderCreateResponse: ');
            if ($result->getStatus() === 'SUCCESS' || $result->getStatus() === 'WARNING_CONTINUE_3DS' || $result->getStatus() === 'WARNING_CONTINUE_REDIRECT') {
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
            $retrieve = PayMethodsCache::getPayMethods($currency, $this->getLanguage(), $this->getVersion());

            if ($retrieve->getStatus() == PayMethodsCache::RETRIEVE_SUCCESS) {
                return [
                    'payByLinks' => $this->reorderPaymentMethods(PayMethodsCache::extractPayByLinks($retrieve), $totalPrice)
                ];
            } else {
                return [
                    'error' => $retrieve->getStatus() . ': ' . OpenPayU_Util::statusDesc($retrieve->getStatus())
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
    public function addOrdersSessionId($orders, $status, $payuIdOrder, $extOrderId, $method)
    {
        $data = [];
        foreach ($orders as $o) {

            $data[] = [
                'id_order' => $o->id,
                'id_cart' => $o->id_cart,
                'id_session' => pSQL($payuIdOrder),
                'ext_order_id' => pSQL($extOrderId),
                'method' => pSQL($method),
                'status' => pSQL($status),
                'create_at' => date('Y-m-d H:i:s'),
            ];
            $this->insertPayuPaymentHistory($status, $o->id, $payuIdOrder);

            SimplePayuLogger::addLog(
                'order',
                __FUNCTION__,
                'DB Insert ' . $o->id,
                $payuIdOrder
            );
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
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "order_payu_payments WHERE id_session = '" . pSQL($id_session) . "'";

        return Db::getInstance()->executeS($sql, true, false);
    }

    /**
     * @param $extOrderId
     *
     * @return array | bool
     */
    public function getOrderPaymentByExtOrderId($extOrderId)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "order_payu_payments WHERE ext_order_id = '" . pSQL($extOrderId) . "'";
        $result = Db::getInstance()->getRow($sql);

        return $result ?: false;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function createInitialDbTable()
    {
        if (Db::getInstance()->ExecuteS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'order_payu_payments"')) {
            $alter_ext_order_id = true;
            $alter_method = true;
            if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'order_payu_payments LIKE "ext_order_id"') == false) {
                $alter_ext_order_id = Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payu_payments ADD ext_order_id VARCHAR(64) NOT NULL AFTER id_session');
            }
            if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'order_payu_payments LIKE "method"') == false) {
                $alter_method = Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payu_payments ADD method VARCHAR(64) NOT NULL AFTER id_session');
            }
            return $alter_ext_order_id && $alter_method;
        } else {
            return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_payu_payments` (
					`id_payu_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`id_order` INT(10) UNSIGNED NOT NULL,
					`id_cart` INT(10) UNSIGNED NOT NULL,
					`id_session` varchar(64) NOT NULL,
					`ext_order_id` VARCHAR(64) NOT NULL,
					`method` varchar(64) NOT NULL,
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
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'order_payu_payments WHERE id_order = ' . (int)$id_order;

        SimplePayuLogger::addLog('order', __FUNCTION__, $sql, $this->payu_order_id);

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
     * @param Customer $customer
     * @param Order $order
     *
     * @return array
     */
    private function getBuyer($customer, $order)
    {
        $billingData = new Address($order->id_address_invoice);

        $buyer = [
            'email' => $customer->email,
            'firstName' => $billingData->firstname,
            'lastName' => $billingData->lastname,
            'language' => $this->getLanguage(),
        ];

        if (!empty($billingData->phone_mobile)) {
            $buyer['phone'] = $billingData->phone_mobile;
        } elseif (!empty($billingData->phone)) {
            $buyer['phone'] = $billingData->phone;
        }

        $shippingData = new Address($order->id_address_delivery);

        $buyer['delivery'] = [
            'street' => $shippingData->address1 . ($shippingData->address2 ? ' ' . $shippingData->address2 : ''),
            'postalCode' => $shippingData->postcode,
            'city' => $shippingData->city
        ];

        if ($shippingData->id_country) {
            $country = new Country($shippingData->id_country);
            $buyer['delivery']['countryCode'] = $country->iso_code;
        }

        return $buyer;
    }

    private function reorderPaymentMethods($payMethods, $totalPrice)
    {
        $filteredPaymethods = [];
        foreach ($payMethods as $payMethod) {
            if ($payMethod->status !== 'ENABLED' || !PayMethodsCache::checkMinMax($payMethod, $totalPrice)) {
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

        SimplePayuLogger::addLog('order', __FUNCTION__, $sql, $this->payu_order_id);
        return Db::getInstance()->executeS($sql, true, false);
    }

    /**
     * @param int $idOrder
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function configureOpuByIdOrder($idOrder)
    {
        $order = new Order($idOrder);
        $currency = Currency::getCurrency($order->id_currency);

        if (!$this->initializeOpenPayU($currency['iso_code'])) {
            throw new \Exception('OPU not properly configured for currency: ' . $currency['iso_code']);
        }
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
                        if (!$this->repaymentEnabled() && Configuration::get('PAYU_PAYMENT_STATUS_CANCELED') !== '0') {
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
    private function getStatesList($withoutChangeStatus = false)
    {
        $states = OrderState::getOrderStates($this->context->language->id);

        if (!is_array($states) || count($states) == 0) {
            return null;
        }

        $list = [];
        if ($withoutChangeStatus) {
            $list[] = [
                'id' => 0,
                'name' => $this->l('Without changing status'),
            ];
        }
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
     * @return array
     */
    private function getCreditWidgetExcludedPaytypes()
    {
        $excludedPayTypes = Configuration::get('PAYU_CREDIT_WIDGET_EXCLUDED_PAYTYPES');
        return empty($excludedPayTypes) ? [] : explode(',', str_replace(' ', '', $excludedPayTypes));
    }

    /**
     * @return string|null
     */
    private function getCurrencyIsoCodeForCreditWidget() {
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        return isset($currency) ? $currency['iso_code'] : null;
    }

    /**
     * @return bool
     */
    private function createHooks()
    {
        $registerStatus = $this->registerHook('displayHeader') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayCheckoutSubtotalDetails') &&
            $this->registerHook('displayCheckoutSummaryTop') &&
            $this->registerHook('actionGetExtraMailTemplateVars');

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
            if (isset($params['order'])) {
                $order = $params['order'];
            } elseif (isset($params['objOrder'])) {
                $order = $params['objOrder'];
            } else {
                return '';
            }

            $order_payment = $this->getOrderPaymentByExtOrderId($extOrder);

            if ($order->id === (int)$order_payment['id_order'] && $order_payment['status'] !== OpenPayuOrderStatus::STATUS_COMPLETED) {
                $this->id_order = $order->id;
                $this->payu_order_id = $order_payment['id_session'];
                $this->updateOrderData();
            }
        }

        $this->context->smarty->assign([
            'payuLogo' => $this->getPayuLogo()
        ]);

        return $this->fetchTemplate('views/templates/hook/paymentReturn.tpl');
    }

    public function hookDisplayCheckoutSubtotalDetails($params)
    {
        if (Configuration::get('PAYU_PROMOTE_CREDIT_CART') === '1'
            && $this->isAnyCreditPaytypeEnabled()) {
            $this->context->smarty->assign([
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),
                'credit_widget_currency_code' => $this->getCurrencyIsoCodeForCreditWidget(),
                'credit_widget_lang' => $this->getLanguage(),
                'credit_widget_excluded_paytypes' => $this->getCreditWidgetExcludedPaytypes()
            ]);
            return $this->display(__FILE__, 'cart-detailed-totals.tpl');
        }
    }

    public function hookDisplayCheckoutSummaryTop($params)
    {
        if (Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY') === '1'
            && $this->isAnyCreditPaytypeEnabled()) {
            $this->context->smarty->assign([
                'cart_total_amount' => $params['cart']->getOrderTotal(),
                'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),
                'credit_widget_currency_code' => $this->getCurrencyIsoCodeForCreditWidget(),
                'credit_widget_lang' => $this->getLanguage(),
                'credit_widget_excluded_paytypes' => $this->getCreditWidgetExcludedPaytypes()
            ]);
            return $this->display(__FILE__, 'cart-summary.tpl');
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if (Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT') === '0'
            || !$this->isAnyCreditPaytypeEnabled()) {
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

                if (is_numeric($price)) {
                    $this->context->smarty->assign([
                        'product_price' => $price,
                        'product_id' => $productId,
                        'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                        'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),
                        'credit_widget_currency_code' => $this->getCurrencyIsoCodeForCreditWidget(),
                        'credit_widget_lang' => $this->getLanguage(),
                        'credit_widget_excluded_paytypes' => $this->getCreditWidgetExcludedPaytypes()
                    ]);
                    return $this->display(__FILE__, 'product.tpl');
                }
            }
        } else {
            $product = $params['product'];
            $current_controller = Tools::getValue('controller');
            if ((
                    ($params['type'] === 'weight' && $current_controller === 'index') ||
                    ($params['type'] === 'after_price' && $current_controller === 'product') ||
                    ($params['type'] === 'weight' && $current_controller === 'category') ||
                    ($params['type'] === 'weight' && $current_controller === 'search')
                ) &&
                isset($product['price_amount']) &&
                is_numeric($product['price_amount'])
            ) {
                $this->context->smarty->assign([
                    'product_price' => $product['price_amount'],
                    'product_id' => $product['id_product'],
                    'credit_pos' => OpenPayU_Configuration::getMerchantPosId(),
                    'credit_pos_key' => substr(OpenPayU_Configuration::getOauthClientSecret(), 0, 2),
                    'credit_widget_currency_code' => $this->getCurrencyIsoCodeForCreditWidget(),
                    'credit_widget_lang' => $this->getLanguage(),
                    'credit_widget_excluded_paytypes' => $this->getCreditWidgetExcludedPaytypes()
                ]);
                return $this->display(__FILE__, 'product.tpl', $this->getCacheId($product['price_amount'] . $product['id_product']));
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
            $order_state->color = '#002124';
            $order_state->module_name = 'payu';

            if (!$order_state->add() || !Configuration::updateValue($state, $order_state->id)) {
                return false;
            }

            copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');

        }

        return $order_state->id;
    }


    /**
     * @param float $value
     * @param string $payuOrderId
     * @param object $order
     *
     * @return array
     */
    private function payuOrderRefund($value, $payuOrderId, $order)
    {
        $this->configureOpuByIdOrder($order->id);

        try {
            $refund = OpenPayU_Refund::create(
                $payuOrderId,
                'Refund to order ' . $order->reference . ' ('. $order->id .')',
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
            return [false, $response->codeLiteral . ' [' . $response->code . '] - <a target="_blank" href="https://developers.payu.com/europe/pl/docs/payment-flows/refunds/#error-codes">developers.payu.com</a>'];
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
     * @param string $paymentMethod
     * @param float $amount
     *
     * @return bool
     */
    private function isPaymentMethodAvailable($paymentMethod, $amount)
    {
        return PayMethodsCache::isPaytypeAvailable($paymentMethod,
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getLanguage(),
                $amount,
                $this->getVersion());
    }

    /**
     * @param float $amount
     *
     * @return bool
     */
    private function isCardAvailable($amount)
    {
        return Configuration::get('PAYU_PAYMENT_METHODS_GRID') !== '1'
            || PayMethodsCache::isPaytypeAvailable('c',
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getLanguage(),
                $amount,
                $this->getVersion(), true);
    }

    /**
     * @param float $amount
     *
     * @return bool
     */
    private function isBlikAvailable($amount)
    {
        return Configuration::get('PAYU_PAYMENT_METHODS_GRID') !== '1'
            || PayMethodsCache::isPaytypeAvailable('blik',
                Currency::getCurrency($this->context->cart->id_currency),
                $this->getLanguage(),
                $amount,
                $this->getVersion(), true);
    }

    /**
     * @param array $payMethods
     * @param float $amount
     *
     * @return string|null
     */
    private function findAvailableCreditPayMethod($payMethods, $amount)
    {
        $availablePayMethods = array_filter($payMethods, function($pm) use ($amount) {
            return $this->isPaymentMethodAvailable($pm, $amount);
        });
        if (count($availablePayMethods) > 1) {
            $errorMsg = 'There can be only one available payment method for a particular provider, '
                . 'more than one indicates a POS configuration error. Erroneous payment methods: '
                . implode(', ', $availablePayMethods);
            SimplePayuLogger::addLog('payment', __FUNCTION__, $errorMsg);
            Logger::addLog($this->displayName . ' ' . $errorMsg, 1);
        }
        return count($availablePayMethods) === 1 ? array_values($availablePayMethods)[0] : null;
    }

    /**
     * @param float $amount
     *
     * @return bool
     */
    private function isAvailableSeparateInstallments($amount)
    {
        return Configuration::get('PAYU_SEPARATE_INSTALLMENTS') === '1'
            && $this->isPaymentMethodAvailable(CreditPaymentMethod::INSTALLMENT, $amount);
    }

    /**
     * @param float $amount
     *
     * @return bool
     */
    private function isAvailableSeparateTwistoSlice($amount)
    {
        return Configuration::get('PAYU_SEPARATE_TWISTO_SLICE') === '1'
            && $this->isPaymentMethodAvailable(CreditPaymentMethod::INSTALLMENT_TWISTO_SLICE, $amount);
    }

    /**
     * @param float $amount
     *
     * @return bool
     */
    private function isAvailableSeparatePragmaPay($amount)
    {
        return Configuration::get('PAYU_SEPARATE_PRAGMA_PAY') === '1'
            && $this->isPaymentMethodAvailable(CreditPaymentMethod::PRAGMA_PAY, $amount);
    }

    /**
     * @return bool
     */
    private function isAnyCreditPaytypeEnabled()
    {
        return PayMethodsCache::isAnyCreditPaytypeEnabled(
            Currency::getCurrency($this->context->cart->id_currency),
            $this->getLanguage(),
            $this->getVersion()
        );
    }

    private function isCreditWidgetEnabled()
    {
        $isEnabledInConfig = ($this->is17()
                && (Configuration::get('PAYU_PROMOTE_CREDIT_CART') === '1' || Configuration::get('PAYU_PROMOTE_CREDIT_SUMMARY') === '1'))
            || Configuration::get('PAYU_PROMOTE_CREDIT_PRODUCT') === '1';
        return $isEnabledInConfig && $this->isAnyCreditPaytypeEnabled();
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
