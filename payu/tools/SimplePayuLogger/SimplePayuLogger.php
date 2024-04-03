<?php

class SimplePayuLogger
{
    const CUSTOM_DATE_FORMAT = 'Y-m-d G:i:s.u';
    private static $logFile = 'payu_%s.log';
    private static $correlationId;

    public static function addLog($type, $function, $message, $order_id = '', $comment = '')
    {
        if (_PS_MODE_DEV_ || Configuration::get('PAYU_LOGGER') === '1') {
            if (!self::$correlationId) {
                self::$correlationId = uniqid('', true);
            }

            $logDir = _PS_ROOT_DIR_ . (version_compare(_PS_VERSION_, '1.7', 'lt') ? '/log/' : '/var/logs/');

            self::writeToLog($function, $message, $order_id, $logDir . sprintf(self::$logFile, $type), $comment);
        }
    }

    public static function formatMessage($message, $order_id, $function, $comment)
    {
        return "[" . self::getTimestamp() . "]" . "[" . self::$correlationId . "]" . ' <' . $order_id . '> ' . ' {' . $function . '} ' . (($comment == '') ? '' : ($comment . PHP_EOL)) . $message . PHP_EOL;
    }

    public static function getTimestamp()
    {
        $date = new DateTime();
        return $date->format(self::CUSTOM_DATE_FORMAT);
    }

    private static function writeToLog($function, $message, $order_id, $logFile, $comment)
    {
        if (!file_exists($logFile)) {
            fopen($logFile, 'a');
        };

        file_put_contents($logFile, self::formatMessage($message, $order_id, $function, $comment), FILE_APPEND);
    }
}
