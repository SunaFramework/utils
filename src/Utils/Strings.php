<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Utils\Exceptions\InvalidStateException;
use Suna\Utils\Exceptions\RegexpException;

/**
 * Strings Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Strings
{
    /**
     * Masks a string to the desired pattern
     *
     * @example '###.###.###-##'
     * @param $val
     * @param $mask
     * @return string
     */
    public static function mask($val, $mask): string
    {
        $maskStr = '';
        $k = 0;
        for($i = 0; $i <= strlen($mask)-1; $i++){
            if($mask[$i] === '#'){
                if(isset($val[$k])){
                    $maskStr .= $val[$k++];
                }
            }else{
                if(isset($mask[$i])){
                    $maskStr .= $mask[$i];
                }
            }
        }
        return $maskStr;
    }

    /**
     * @param string $func
     * @param array $args
     * @return mixed
     * @throws RegexpException
     */
    public static function pcre(string $func, array $args): mixed
    {
        $res = Callback::invokeSafe($func, $args, function (string $message) use ($args): void {
            throw new RegexpException($message . ' in pattern: ' . implode(' or ', (array) $args[0]));
        });

        if (($code = preg_last_error()) && ($res === null || !in_array($func, ['preg_filter', 'preg_replace_callback', 'preg_replace'], true))) {
            throw new RegexpException((RegexpException::MESSAGES[$code] ?? 'Unknown error')
                . ' (pattern: ' . implode(' or ', (array) $args[0]) . ')', $code);
        }
        return $res;
    }

    /**
     * @param string $subject
     * @param string|array $pattern
     * @param string|callable $replacement
     * @param int $limit
     * @return string
     * @throws RegexpException
     */
    public static function replace(string $subject, string|array $pattern, string|callable $replacement = '', int $limit = -1): string {
        if (is_object($replacement) || is_array($replacement)) {
            if (!is_callable($replacement, false, $textual)) {
                throw new InvalidStateException("Callback '$textual' is not callable.");
            }

            return self::pcre('preg_replace_callback', [$pattern, $replacement, $subject, $limit]);
        } elseif (is_array($pattern) && is_string(key($pattern))) {
            $replacement = array_values($pattern);
            $pattern = array_keys($pattern);
        }

        return self::pcre('preg_replace', [$pattern, $replacement, $subject, $limit]);
    }

    /**
     * @param string $str
     * @param int $level
     * @param string $chars
     * @return string
     * @throws RegexpException
     */
    public static function indent(string $str, int $level = 1, string $chars = "\t"): string
    {
        if ($level > 0) {
            $pattern = '#(?:^|[\r\n]+)(?=[^\r\n])#';
            $str = Strings::replace($str, $pattern, '$0' . str_repeat($chars, $level));
        }
        return $str;
    }

    /**
     * @param string $s
     * @return string
     */
    public static function normalizeNewLines(string $s): string
    {
        return str_replace(["\r\n", "\r"], "\n", $s);
    }

    /**
     * @param string $s
     * @return string
     * @throws \Suna\Utils\Exceptions\RegexpException
     */
    public static function normalize(string $s): string
    {
        if (class_exists('Normalizer', false) && ($n = \Normalizer::normalize($s, \Normalizer::FORM_C)) !== false) {
            $s = $n;
        }

        $s = self::normalizeNewLines($s);
        $s = self::pcre('preg_replace', ['#[\x00-\x08\x0B-\x1F\x7F-\x9F]+#u', '', $s]);
        $s = self::pcre('preg_replace', ['#[\t ]+$#m', '', $s]);
        return trim($s, "\n");
    }

}