<?php

class PayuApplepayModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        // Tell PrestaShop not to load header, footer, or layout templates
        $this->ajax = true;
        parent::initContent();
    }

    public function displayAjax()
    {
        $domainName = Configuration::get('PAYU_APPLE_PAY_DOMAIN_NAME');
        $displayName = Configuration::get('PAYU_APPLE_PAY_DISPLAY_NAME');
        $currencyData = Currency::getCurrency($this->context->cart->id_currency);
        $currency = isset($currencyData['iso_code']) ? $currencyData['iso_code'] : null;

        if (!$this->module->active || Configuration::get('PAYU_SEPARATE_APPLE_PAY') !== '1') {
            $this->setBadRequestHeader();
            die('Apple Pay is not enabled');
        }

        if (!$currency || !$domainName || !$displayName) {
            $this->setBadRequestHeader();
            die('Apple Pay is not properly configured');
        }

        if (!$this->module->initializeOpenPayU($currency)) {
            $this->setBadRequestHeader();
            die('OPU not properly configured for currency: ' . $currency);
        }
        try {
            $sessionResponse = OpenPayU_ApplePay::createSession(
                $domainName,
                $displayName
            );
            if ($sessionResponse->getStatus() === 'SUCCESS') {
                header('Content-Type: application/json');
                echo json_encode($sessionResponse->getResponse());
                exit;
            }

            $this->setBadRequestHeader();
            die($sessionResponse->getError());

        } catch (\Exception $e) {
            $this->setBadRequestHeader();
            die($e->getMessage());
        }
    }

    private function setBadRequestHeader()
    {
        header('HTTP/1.1 400 Bad Request', true, 400);
    }
}
