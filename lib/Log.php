<?php

namespace RDN\Error;

use Bitrix\Main\Application;
use Psr\Log\LogLevel;
use RDN\Error\Entities\Internals\LogTable;

class Log
{
    public const ERROR_LEVEL__CRITICAL = 'CRITICAL';
    public const ERROR_LEVEL__ERROR = 'ERROR';
    public const ERROR_LEVEL__WARNING = 'WARNING';

    public const SECTION__PARSING_SITES = 'PARSING SITES';
    public const SECTION__PRICE_ANALYTICS = 'PRICE ANALYTICS';

    public static function add(
        string $message,
        $data = null,
        string $section = null,
        string $errorLevel = null
    ): void
    {
        if (defined('STOP_RDN_ERROR')) {
            return;
        }

        try {
            $arTrace = self::getTrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $arCalled = self::getCalledInfo($arTrace);

            $fields = [
                'MESSAGE' => $message,
                'ENTITY' => $arCalled['ENTITY'],
                'FUNCTION' => $arCalled['FUNCTION'],
                'CALLED_FROM' => $arCalled['CALLED_FROM'],
                'DUMP' => self::dump($data),
            ];

            $checkSum = self::createCheckSum($fields);
            $hashStorage = new HashStorage(dirname(__DIR__, 4));

            if (! $hashStorage->has($checkSum)){

                $savingResult = LogTable::createObject()
                    ->setMessage($fields['MESSAGE'])
                    ->setEntity($fields['ENTITY'])
                    ->setFunction($fields['FUNCTION'])
                    ->setCalledFrom($fields['CALLED_FROM'])
                    ->setDump($fields['DUMP'])
                    ->setCheckSum($checkSum)
                    ->setSection($section)
                    ->setErrorLevel($errorLevel)
                    ->save();

                if (! $savingResult->isSuccess()) {
                    throw new \Exception(implode(', ', $savingResult->getErrorMessages()));
                } else {
                    $hashStorage->add($checkSum);
                }
            }
        }
        catch (\Exception $e) {
            Application::getInstance()
                ->createExceptionHandlerLog()
                ->write($e, LogLevel::ERROR);
        }
    }

    protected static function createCheckSum(array $fields): string
    {
        $str = implode('', $fields);
        return md5($str);
    }

    protected static function dump($data = null): ?string
    {
        if (! empty($data)) {
            return print_r($data, true);
        }

        return null;
    }

    protected static function getTrace(int $options = DEBUG_BACKTRACE_IGNORE_ARGS, int $limit = 0): array
    {
        ob_start();
        debug_print_backtrace($options, $limit);
        $trace = ob_get_contents();
        ob_end_clean();

        $arTrace = explode("\n", $trace);
        array_pop($arTrace); // Remove last empty from stack

        return $arTrace;
    }

    protected static function getCalledInfo(array $arTrace): array
    {
        $arCall = preg_split('/called at /', end($arTrace)) ?: [];

        $entity = '';
        $function = '';

        if (! empty($arTrace[2])) {
            $arEntity = explode( '::', $arCall[0]);

            if (! empty($functionName = $arEntity[1])) {
                $function = $functionName;
                $entity = substr($arEntity[0], 4);
            } elseif (! empty($functionName = $arEntity[0])) {
                $function = substr($functionName, 4);
            }
        }

        $calledFrom = substr($arCall[1], 1, -1);
        if (! empty($root = Application::getDocumentRoot())) {
            $calledFrom = str_replace($root, '', $calledFrom);
        }

        return [
            'ENTITY' => $entity,
            'FUNCTION' => $function,
            'CALLED_FROM' => $calledFrom
        ];
    }
}
