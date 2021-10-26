<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Exceptions\InvalidStateException;
use Suna\Exceptions\RegexpException;

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
     * @throws \Suna\Exceptions\RegexpException
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

    /**
     * @param string $s
     * @param string|null $charlist
     * @param bool $lower
     * @return string
     * @throws \Suna\Exceptions\RegexpException
     */
    public static function webalize(string $s, string $charlist = null, bool $lower = true): string
    {
        $s = self::toAscii($s);
        if ($lower) {
            $s = mb_strtolower($s);
        }
        $s = self::pcre('preg_replace', ['#[^a-z0-9' . ($charlist !== null ? preg_quote($charlist, '#') : '') . ']+#i', '-', $s]);
        return trim($s, '-');
    }

    /**
     * @note Get From Web
     * @param string $s
     * @return string
     * @throws \Suna\Exceptions\RegexpException
     */
    public static function toAscii(string $s): string
    {
        $iconv = defined('ICONV_IMPL') ? trim(ICONV_IMPL, '"\'') : null;
        static $transliterator = null;
        if ($transliterator === null) {
            if (class_exists('Transliterator', false)) {
                $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
            } else {
                trigger_error(__METHOD__ . "(): it is recommended to enable PHP extensions 'intl'.", E_USER_NOTICE);
                $transliterator = false;
            }
        }

        $s = self::pcre('preg_replace', ['#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u', '', $s]);

        $s = strtr($s, ["\u{201E}" => '"', "\u{201C}" => '"', "\u{201D}" => '"', "\u{201A}" => "'", "\u{2018}" => "'", "\u{2019}" => "'", "\u{B0}" => '^', "\u{42F}" => 'Ya', "\u{44F}" => 'ya', "\u{42E}" => 'Yu', "\u{44E}" => 'yu', "\u{c4}" => 'Ae', "\u{d6}" => 'Oe', "\u{dc}" => 'Ue', "\u{1e9e}" => 'Ss', "\u{e4}" => 'ae', "\u{f6}" => 'oe', "\u{fc}" => 'ue', "\u{df}" => 'ss']); // „ “ ” ‚ ‘ ’ ° Я я Ю ю Ä Ö Ü ẞ ä ö ü ß
        if ($iconv !== 'libiconv') {
            $s = strtr($s, ["\u{AE}" => '(R)', "\u{A9}" => '(c)', "\u{2026}" => '...', "\u{AB}" => '<<', "\u{BB}" => '>>', "\u{A3}" => 'lb', "\u{A5}" => 'yen', "\u{B2}" => '^2', "\u{B3}" => '^3', "\u{B5}" => 'u', "\u{B9}" => '^1', "\u{BA}" => 'o', "\u{BF}" => '?', "\u{2CA}" => "'", "\u{2CD}" => '_', "\u{2DD}" => '"', "\u{1FEF}" => '', "\u{20AC}" => 'EUR', "\u{2122}" => 'TM', "\u{212E}" => 'e', "\u{2190}" => '<-', "\u{2191}" => '^', "\u{2192}" => '->', "\u{2193}" => 'V', "\u{2194}" => '<->']); // ® © … « » £ ¥ ² ³ µ ¹ º ¿ ˊ ˍ ˝ ` € ™ ℮ ← ↑ → ↓ ↔
        }

        if ($transliterator) {
            $s = $transliterator->transliterate($s);
            if ($iconv === 'glibc') {
                $s = strtr($s, '?', "\x01");
                $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
                $s = str_replace(['?', "\x01"], ['', '?'], $s);
            } elseif ($iconv === 'libiconv') {
                $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            } else {
                $s = self::pcre('preg_replace', ['#[^\x00-\x7F]++#', '', $s]); // remove non-ascii chars
            }
        } elseif ($iconv === 'glibc' || $iconv === 'libiconv') {
            $s = strtr($s, '`\'"^~?', "\x01\x02\x03\x04\x05\x06");
            if ($iconv === 'glibc') {
                $s = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $s);
                $s = strtr(
                    $s,
                    "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe\x96\xa0\x8b\x97\x9b\xa6\xad\xb7",
                    'ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt- <->|-.',
                );
                $s = self::pcre('preg_replace', ['#[^\x00-\x7F]++#', '', $s]);
            } else {
                $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            }
            $s = str_replace(['`', "'", '"', '^', '~', '?'], '', $s);
            $s = strtr($s, "\x01\x02\x03\x04\x05\x06", '`\'"^~?');
        } else {
            $s = self::pcre('preg_replace', ['#[^\x00-\x7F]++#', '', $s]);
        }

        return $s;
    }

}