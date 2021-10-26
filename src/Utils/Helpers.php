<?php declare(strict_types=1);

namespace Suna\Utils;

/**
 * Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Helpers
{
    /**
     * Converts false to null
     * @note does not change other values.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function falseToNull(mixed $value): mixed
    {
        return $value === false ? null : $value;
    }

    /**
     * @param callable $func
     * @return string
     * @throws \Throwable
     */
    public static function capture(callable $func): string
    {
        ob_start(function () {});
        try {
            $func();
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * @return string
     */
    public static function getLastError(): string
    {
        $message = error_get_last()['message'] ?? '';
        $message = ini_get('html_errors') ? Html::toText($message) : $message;
        return preg_replace('#^\w+\(.*?\): #', '', $message);
    }
}