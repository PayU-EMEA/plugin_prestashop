<?php

class SimplePayuLogger
{
    const CUSTOM_DATE_FORMAT = 'Y-m-d G:i:s.u';
    private static $logFile = _PS_ROOT_DIR_ . '/var/logs/payu_%s.log';
    private static $correlationId;

    public static function addLog($type, $function, $message, $order_id = '', $comment = '')
    {
        if (_PS_MODE_DEV_) {
            if (!self::$correlationId) {
                self::$correlationId = uniqid('', true);
            }

            self::writeToLog($function, $message, $order_id, sprintf(self::$logFile, $type), $comment);
        }
    }

    public static function formatMessage($message, $order_id, $function, $comment)
    {
        return "[" . self::getTimestamp() . "]"."[" . self::$correlationId . "]".' <' . $order_id . '> ' . ' {' . $function . '} ' . (($comment == '')?'':($comment . PHP_EOL)) . $message . PHP_EOL;
    }

    public static function getTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));
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
