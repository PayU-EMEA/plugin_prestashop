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

if (!defined('_PS_VERSION_'))
    exit;

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
    public $payu_payment_id = null;

    /** @var string  */
    private $extOrderId = '';

    public function __construct()
    {
        $this->name = 'payu';
        $this->tab = 'payments_gateways';
        $this->version = '2.5.0';
        $this->author = 'PayU';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.4.4', 'max' => '1.6');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        parent::__construct();

        $this->displayName = $this->l('PayU');
        $this->description = $this->l('Accepts payments by PayU');

        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');

        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }

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
            $this->createHooks() &&
            Configuration::updateValue('PAYU_MC_POS_ID', '') &&
            Configuration::updateValue('PAYU_MC_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_ID', '') &&
            Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_SECRET', '') &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', $this->addNewOrderState('PAYU_PAYMENT_STATUS_PENDING',
                array('en' => 'PayU payment pending', 'pl' => 'Płatność PayU rozpoczęta'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', $this->addNewOrderState('PAYU_PAYMENT_STATUS_SENT',
                array('en' => 'PayU payment waiting for confirmation', 'pl' => 'Płatność PayU oczekuje na odbiór'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', $this->addNewOrderState('PAYU_PAYMENT_STATUS_CANCELED',
                array('en' => 'PayU payment canceled', 'pl' => 'Płatność PayU anulowana'))) &&
            Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', 2) &&
            Configuration::updateValue('PAYU_RETRIEVE', false)
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
            !Configuration::deleteByName('PAYU_RETRIEVE')
        ) {
            return false;
        }
        return true;
    }


    public function initializeOpenPayU($currencyIsoCode)
    {
        $payuPosId = self::unSerialize(Configuration::get('PAYU_MC_POS_ID'));
        $payuSignatureKey = self::unSerialize(Configuration::get('PAYU_MC_SIGNATURE_KEY'));
        $payuOauthClientId = self::unSerialize(Configuration::get('PAYU_MC_OAUTH_CLIENT_ID'));
        $payuOauthClientSecret = self::unSerialize(Configuration::get('PAYU_MC_OAUTH_CLIENT_SECRET'));

        if (!is_array($payuPosId) ||
            !is_array($payuSignatureKey) ||
            !$payuPosId[$currencyIsoCode] ||
            !$payuSignatureKey[$currencyIsoCode]
        ) {
            return false;
        }

        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setMerchantPosId($payuPosId[$currencyIsoCode]);
        OpenPayU_Configuration::setSignatureKey($payuSignatureKey[$currencyIsoCode]);
        if ($payuOauthClientId[$currencyIsoCode] && $payuOauthClientSecret[$currencyIsoCode]) {
            OpenPayU_Configuration::setOauthClientId($payuOauthClientId[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthClientSecret($payuOauthClientSecret[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthTokenCache(new OauthCachePresta());
        }
        OpenPayU_Configuration::setSender('Prestashop ver ' . _PS_VERSION_ . '/Plugin ver ' . $this->version);

        return true;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = null;
        $errors = array();

        if (Tools::isSubmit('submit' . $this->name)) {
            if (!Configuration::updateValue('PAYU_MC_POS_ID', serialize(Tools::getValue('PAYU_MC_POS_ID'))) ||
                !Configuration::updateValue('PAYU_MC_SIGNATURE_KEY', serialize(Tools::getValue('PAYU_MC_SIGNATURE_KEY'))) ||
                !Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_ID', serialize(Tools::getValue('PAYU_MC_OAUTH_CLIENT_ID'))) ||
                !Configuration::updateValue('PAYU_MC_OAUTH_CLIENT_SECRET', serialize(Tools::getValue('PAYU_MC_OAUTH_CLIENT_SECRET'))) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_PENDING', (int)Tools::getValue('PAYU_PAYMENT_STATUS_PENDING')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_SENT', (int)Tools::getValue('PAYU_PAYMENT_STATUS_SENT')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_COMPLETED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_COMPLETED')) ||
                !Configuration::updateValue('PAYU_PAYMENT_STATUS_CANCELED', (int)Tools::getValue('PAYU_PAYMENT_STATUS_CANCELED')) ||
                !Configuration::updateValue('PAYU_RETRIEVE', (bool)Tools::getValue('PAYU_RETRIEVE'))
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
        } else {
            $this->migrateToMulticurrency();
        }

        return $output . $this->displayForm();
    }

    /**
     * @return mixed
     */
    public function displayForm()
    {

        $this->context->smarty->assign(array(
            'PAYU_MC_POS_ID' => self::unSerialize(Configuration::get('PAYU_MC_POS_ID')),
            'PAYU_MC_SIGNATURE_KEY' => self::unSerialize(Configuration::get('PAYU_MC_SIGNATURE_KEY')),
            'PAYU_MC_OAUTH_CLIENT_ID' => self::unSerialize(Configuration::get('PAYU_MC_OAUTH_CLIENT_ID')),
            'PAYU_MC_OAUTH_CLIENT_SECRET' => self::unSerialize(Configuration::get('PAYU_MC_OAUTH_CLIENT_SECRET')),
            'PAYU_PAYMENT_STATES_OPTIONS' => $this->getStatesList(),
            'PAYU_PAYMENT_STATUS_PENDING' => Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
            'PAYU_PAYMENT_STATUS_SENT' => Configuration::get('PAYU_PAYMENT_STATUS_SENT'),
            'PAYU_PAYMENT_STATUS_COMPLETED' => Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'),
            'PAYU_PAYMENT_STATUS_CANCELED' => Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'),
            'PAYU_RETRIEVE' => Configuration::get('PAYU_RETRIEVE'),
            'PAYU_RETRIEVE_OPTIONS' => array(
                array(
                    'id' => '1',
                    'name' => $this->l('Yes')
                ),
                array(
                    'id' => '0',
                    'name' => $this->l('No')
                )
            ),
            'currencies' => $currency_list = Currency::getCurrencies(),
        ));

        return $this->fetchTemplate('/views/templates/admin/office.tpl');
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function fetchTemplate($name)
    {
        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
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
        $output = '<link type="text/css" rel="stylesheet" href="' . _MODULE_DIR_ . $this->name . '/css/payu.css" />';

        $vieworder = Tools::getValue('vieworder');
        $id_order = Tools::getValue('id_order');

        $refundable = false;

        if ($vieworder !== false && $id_order !== false) {
            $order = new Order($id_order);
            $order_payment = $this->getLastOrderPaymentByOrderId($id_order);

            if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                $order_state = OrderHistory::getLastOrderState($id_order);
                $order_state_id = $order_state->id;
            } else {
                $order_state_id = $order->current_state;
            }

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

                    $history->addWithemail(true, array());

                    if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
                        Tools::redirectAdmin('index.php?tab=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                    } else {
                        Tools::redirectAdmin('index.php?controller=AdminOrders&vieworder&id_order=' . $id_order . '&token=' . Tools::getValue('token'));
                    }
                }
            }
        }

        $this->context->smarty->assign('payu_refund_errors', $refund_errors);

        if (version_compare(_PS_VERSION_, '1.6', 'lt')) {
            $template = $output . $this->fetchTemplate('/views/templates/admin/header.tpl');
        } else {
            $template = $output . $this->fetchTemplate('/views/templates/admin/header16.tpl');
        }
        return $template;
    }

    public function hookHeader()
    {
        if (version_compare(_PS_VERSION_, '1.6', 'lt')) {
            Tools::addCSS(($this->_path) . 'css/payu.css', 'all');
            Tools::addJS(($this->_path) . 'js/payu.js', 'all');
        } else {
            $this->context->controller->addCSS(($this->_path) . 'css/payu.css', 'all');
            $this->context->controller->addJS(($this->_path) . 'js/payu.js', 'all');
        }
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

            return $this->fetchTemplate('/views/templates/hook/retryPayment.tpl');
        }
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

        $this->context->smarty->assign(array(
            'image' => $this->getPayuLogo(),
            'actionUrl' => $link)
        );

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
    public function hookAdminOrder($params)
    {

        $this->id_order = $params['id_order'];
        $output = '';


        if (Tools::getValue('cancelPayuOrder')) {
            $this->payu_order_id = Tools::getValue('cancelPayuOrder');

            $updateOrderStatus = $this->sendPaymentUpdate(OpenPayuOrderStatus::STATUS_CANCELED);

            $this->context->smarty->assign(array(
                'PAYU_CANCEL_ORDER_MESSAGE' => $updateOrderStatus !== true ? $this->displayError($updateOrderStatus['message']) : $this->displayConfirmation($this->l('Update status request has been sent'))
            ));

        }

        $order_payment = $this->getLastOrderPaymentByOrderId($params['id_order']);

        $this->context->smarty->assign(array(
            'PAYU_ORDERS' => $this->getOrdersByOrderId($params['id_order']),
            'PAYU_ORDER_ID' => $this->id_order
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
                    $output .= $this->displayError($this->l('Update status request has not been completed correctly.'.' '.$updateOrderStatus['message']));
                }
            }

            $this->context->smarty->assign(array(
                'PAYU_PAYMENT_STATUS_OPTIONS' => $this->getPaymentAcceptanceStatusesList(),
                'PAYU_PAYMENT_STATUS' => $order_payment['status'],
                'PAYU_PAYMENT_ACCEPT' => $isConfirmable
            ));
        }

        return $output . $this->fetchTemplate('/views/templates/admin/status.tpl') ;
    }

    /**
     * @param int $order_id
     * @param int $order_state
     * @return bool
     */
    public function hasRetryPayment($order_id, $order_state)
    {
        $payuOrder = $this->getLastOrderPaymentByOrderId($order_id);

        if ($payuOrder['status'] != OpenPayuOrderStatus::STATUS_CANCELED
            || $order_state != (int)Configuration::get('PAYU_PAYMENT_STATUS_CANCELED')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $payMethod
     * @return array|bool
     */
    public function orderCreateRequest($payMethod = null)
    {

        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        $currency = Currency::getCurrency($this->cart->id_currency);

        if (!$this->initializeOpenPayU($currency['iso_code'])) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OPU not properly configured for currency: '.$currency['iso_code']);
            Logger::addLog($this->displayName . ' ' . 'OPU not properly configured for currency: '.$currency['iso_code'], 1);

            return false;
        }

        $return_array = array();

        $items = array();

        $cart_products = $this->cart->getProducts();

        //discounts and cart rules
        list($items, $total) = $this->getDiscountsAndCartRules($items, $cart_products);
        // Wrapping fees
        list($wrapping_fees_tax_inc, $items, $total) = $this->getWrappingFees($items, $total);

        $carrier = $this->getCarrier($this->cart);
        $grand_total = $this->getGrandTotal($wrapping_fees_tax_inc, $total);

        if (!empty($this->cart->id_customer)) {
            $customer = new Customer((int)$this->cart->id_customer);

            if ($customer->email) {
                $customer_sheet = $this->getCustomer($customer);

            }
        }
        //prepare data for OrderCreateRequest
        $ocreq = $this->prepareOrder($items, $customer_sheet, $currency, $grand_total, $carrier, $payMethod, $this->cart->id);

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
                $return_array = array(
                    'error' => $result->getError() . ' ' . $result->getMessage()
                );
                SimplePayuLogger::addLog('order', __FUNCTION__, 'OpenPayU_Order::create($ocreq) NOT success!! ' . $this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage(), $this->payu_order_id));
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage()), 1);
            }

        } catch (Exception $e) {
            $return_array = array(
                'error' => $e->getCode() . ' ' . $e->getMessage()
            );
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Exception catched! ' . $this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()));
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()), 1);
        }
        return $return_array;
    }

    /**
     * @return array
     */
    public function orderCreateRequestByOrder($payMethod = null)
    {

        SimplePayuLogger::addLog('order', __FUNCTION__, 'Entrance: ', $this->payu_order_id);
        $currency = Currency::getCurrency($this->order->id_currency);

        if (!$this->initializeOpenPayU($currency['iso_code'])) {
            SimplePayuLogger::addLog('order', __FUNCTION__, 'OPU not properly configured for currency: '.$currency['iso_code']);
            Logger::addLog($this->displayName . ' ' . 'OPU not properly configured for currency: '.$currency['iso_code'], 1);

            return false;
        }

        $return_array = array();

        $grand_total = $this->toAmount($this->order->total_paid);

        $carrier = null;

        $items = array(
            'products' => array(
                array(
                    'quantity' => 1,
                    'name' => 'Order reference: ' . $this->order->reference,
                    'unitPrice' => $grand_total
                )
            )
        );

        if (!empty($this->order->id_customer)) {
            $customer = new Customer($this->order->id_customer);
            $customer_sheet = array(
                'email' => $customer->email,
                'firstName' => $customer->firstname,
                'lastName' => $customer->lastname
            );
        }

        //prepare data for OrderCreateRequest
        $ocreq = $this->prepareOrder($items, $customer_sheet, $currency, $grand_total, $carrier, $payMethod, $this->order->id, $this->order->id_cart);

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
                $return_array = array(
                    'error' => $result->getError() . ' ' . $result->getMessage()
                );
                SimplePayuLogger::addLog('order', __FUNCTION__, 'OpenPayU_Order::create($ocreq) NOT success!! ' . $this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage(), $this->payu_order_id));
                Logger::addLog($this->displayName . ' ' . trim($result->getError() . ' ' . $result->getMessage()), 1);
            }

        } catch (Exception $e) {
            $return_array = array(
                'error' => $e->getCode() . ' ' . $e->getMessage()
            );
            SimplePayuLogger::addLog('order', __FUNCTION__, 'Exception catched! ' . $this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()));
            Logger::addLog($this->displayName . ' ' . trim($e->getCode() . ' ' . $e->getMessage()), 1);
        }
        return $return_array;
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
                    'payByLinks' => $this->moveCardToFirstPositionAndRemoveDisabledTest($response->payByLinks)
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

    public function getPayuLogo()
    {
        if (version_compare(_PS_VERSION_, '1.6', 'lt')) {
            return _PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/img/payu_logo.png';
        } else {
            return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/img/payu_logo.png');
        }
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
     * Define our own Tools::unSerialize() (since 1.5), to be available in PrestaShop 1.4
     */
    protected static function unSerialize($serialized)
    {
        if (method_exists('Tools', 'unserialize'))
            return Tools::unSerialize($serialized);

        if (is_string($serialized) && (strpos($serialized, 'O:') === false || !preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized)))
            return @unserialize($serialized);

        return false;
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

        SimplePayuLogger::addLog('order', __FUNCTION__, 'DB Insert '.$sql, $payuIdOrder);

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
     */
    private function createInitialDbTable()
    {
        if (Db::getInstance()->ExecuteS('SHOW TABLES LIKE ' . _DB_PREFIX_ . 'order_payu_payments')) {
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
			WHERE id_session="' . pSQL($this->payu_order_id) . '" AND status != "'.OpenPayuOrderStatus::STATUS_COMPLETED.'" AND status != "'.pSQL($status).'"';

        if ($previousStatus) {
            $sql .= ' AND status = "'.$previousStatus.'"';
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
        } else {
            $link = new Link();
            $order_complete_link = $this->getModuleAddress() . 'backward_compatibility/success.php';
            $order_notify_link = $this->getModuleAddress() . 'backward_compatibility/notification.php';
        }

        return array(
          'notify' => $order_notify_link,
          'continue' => $order_complete_link
        );
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
     * @param $currency
     * @param $grand_total
     * @param $carrier
     * @param $payMethod
     * @param $idOrder
     * @param $idCart
     * @return array
     */
    private function prepareOrder($items, $customer_sheet, $currency, $grand_total, $carrier, $payMethod, $idCart, $idOrder = null )
    {
        $ocreq = array();

        $ocreq['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $ocreq['description'] = $this->l('Order for cart: ') . $idCart . $this->l(' from the store: ') . Configuration::get('PS_SHOP_NAME');
        $ocreq['products'] = $items['products'];
        if ($carrier && is_array($carrier)) {
            array_push($ocreq['products'], $carrier);
        }
        $ocreq['buyer'] = $customer_sheet;
        $ocreq['customerIp'] = $this->getIP();

        $links = $this->getLinks();
        $ocreq['notifyUrl'] = $links['notify'];
        $ocreq['continueUrl'] = $links['continue'] . '?id=' . $this->extOrderId;
        $ocreq['currencyCode'] = $currency['iso_code'];
        $ocreq['totalAmount'] = $grand_total;
        $ocreq['extOrderId'] = $this->extOrderId;
        $ocreq['settings']['invoiceDisabled'] = true;

        if ($payMethod !== null) {
            $ocreq['payMethods'] = array(
                'payMethod' => array(
                    'type' => 'PBL',
                    'value' => $payMethod
                )
            );
        }

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

    private function moveCardToFirstPositionAndRemoveDisabledTest($payMethods)
    {
        foreach ($payMethods as $id => $payMethod) {
            if ($payMethod->value == 'c') {
                $cart = $payMethod;
                unset($payMethods[$id]);
                array_unshift($payMethods, $cart);
            }
            if ($payMethod->value == 't' && $payMethod->status != 'ENABLED') {
                $cart = $payMethod;
                unset($payMethods[$id]);
            }
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


    private function migrateToMulticurrency()
    {
        $this->migrateParameter('PAYU_POS_ID', 'PAYU_MC_POS_ID');
        $this->migrateParameter('PAYU_SIGNATURE_KEY', 'PAYU_MC_SIGNATURE_KEY');
        $this->migrateParameter('PAYU_OAUTH_CLIENT_ID', 'PAYU_MC_OAUTH_CLIENT_ID');
        $this->migrateParameter('PAYU_OAUTH_CLIENT_SECRET', 'PAYU_MC_OAUTH_CLIENT_SECRET');
    }

    private function migrateParameter($oldParameter, $newParameter)
    {
        if (Configuration::get($oldParameter) && !Configuration::get($newParameter)) {
            $currencies = Currency::getCurrencies();
            foreach ($currencies as $currency) {
                $newConfig[$currency['iso_code']] = Configuration::get($oldParameter);
            }
            Configuration::updateValue($newParameter, serialize($newConfig));
        }
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
            $order_state_id = $this->getCurrentPrestaOrderState();

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

    private function addProductsToOrder($cartProducts, &$total)
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
     * @return array|null
     */
    private function getCarrier()
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
                        'name' => $selected_carrier->name,
                        'quantity' => 1,
                        'unitPrice' => $this->toAmount($price)
                    );

                }
            }
        }

        return $carrier_list;
    }

    /**
     * @param bool $http
     * @param bool $entities
     * @return string
     */
    private function getModuleAddress($http = true, $entities = true)
    {
        return $this->getShopDomainAddress($http, $entities) . (__PS_BASE_URI__ . 'modules/' . $this->name . '/');
    }

    /**
     * @return string
     */
    private function getShopDomainAddress()
    {
        if (method_exists('Tools', 'getShopDomainSsl')) {
            return Tools::getShopDomainSsl(false, false);
        }

        if (!($domain = Configuration::get('PS_SHOP_DOMAIN_SSL'))) {
            $domain = Tools::getHttpHost();
        }

        return $domain;
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
        if (version_compare(_PS_VERSION_, '1.5', 'lt')) {
            return ($this->registerHook('header') &&
                $this->registerHook('payment') &&
                $this->registerHook('paymentReturn') &&
                $this->registerHook('backOfficeHeader') &&
                $this->registerHook('adminOrder'));

        } else {
            return ($this->registerHook('header') &&
                $this->registerHook('payment') &&
                $this->registerHook('displayPaymentEU') &&
                $this->registerHook('paymentReturn') &&
                $this->registerHook('backOfficeHeader') &&
                $this->registerHook('adminOrder') &&
                $this->registerHook('displayOrderDetail'));
        }
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
                }
                elseif ($status == OpenPayuOrderStatus::STATUS_COMPLETED) {
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
        return array (
            'message' => $this->l('Order status update hasn\'t been sent')
        );
    }

    /**
     * @param string $state
     * @param array $names
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

}
