<?php

class PayuStatusModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }


    public function displayAjax()
    {
        $order = new Order(Tools::getValue('id_order'));

        if ($order->secure_key === Tools::getValue('key')) {
            switch ($order->current_state){
                case Configuration::get('PAYU_PAYMENT_STATUS_COMPLETED'):
                    $status = 'COMPLETED';
                    break;
                case Configuration::get('PAYU_PAYMENT_STATUS_CANCELED'):
                    $status = 'CANCELED';
                    break;
                default:
                    $status = 'PENDING';
                    break;
            }
        } else {
            $status = 'PENDING';
        }

        $params = [
            'status' => $status
        ];

        header('Content-Type: application/json');

        echo json_encode($params, JSON_THROW_ON_ERROR);
    }
}
