<?php
class CreditPaymentMethod {
    const INSTALLMENT = 'ai';
    const DELAYED_PAYMENT_KLARNA_PLN = 'dpkl';
    const DELAYED_PAYMENT_KLARNA_CZK    = 'dpklczk';
    const DELAYED_PAYMENT_KLARNA_EUR = 'dpkleur';
    const DELAYED_PAYMENT_KLARNA_HUF = 'dpklhuf';
    const DELAYED_PAYMENT_TWISTO_PLN = 'dpt';
    const DELAYED_PAYMENT_TWISTO_CZK = 'dpcz';
    const DELAYED_PAYMENT_TWISTO_SLICE = 'dpts';
    const DELAYED_PAYMENT_PAYPO_PLN = 'dpp';
    const DELAYED_PAYMENT_PAYPO_RON = 'dppron';

    public static function getAll()
    {
        return [
            self::INSTALLMENT,
            self::DELAYED_PAYMENT_KLARNA_PLN,
            self::DELAYED_PAYMENT_KLARNA_CZK,
            self::DELAYED_PAYMENT_KLARNA_EUR,
            self::DELAYED_PAYMENT_KLARNA_HUF,
            self::DELAYED_PAYMENT_TWISTO_PLN,
            self::DELAYED_PAYMENT_TWISTO_CZK,
            self::DELAYED_PAYMENT_TWISTO_SLICE,
            self::DELAYED_PAYMENT_PAYPO_PLN,
            self::DELAYED_PAYMENT_PAYPO_RON
        ];
    }
}
