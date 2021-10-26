<?php declare(strict_types=1);

namespace Suna\Utils;

/**
 * HTML Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Html
{
    /**
     * @param string $html
     * @param int $flags
     * @param string $encoding
     * @return string
     */
    public static function toText(string $html, int $flags = ENT_QUOTES | ENT_HTML5, string $encoding = 'UTF-8'): string
    {
        return html_entity_decode(strip_tags($html), $flags, $encoding);
    }

}