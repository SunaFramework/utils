<?php declare(strict_types=1);

namespace Suna\Utils\Exceptions;

/**
 * The exception that indicates the last Regexp exec error.
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class RegexpException extends \Exception
{
    public const MESSAGES = [
        PREG_INTERNAL_ERROR => 'Internal error',
        PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted',
        PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted',
        PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
        PREG_BAD_UTF8_OFFSET_ERROR => 'Offset didn\'t correspond to the begin of a valid UTF-8 code point',
        6 => 'Failed due to limited JIT stack space', // PREG_JIT_STACKLIMIT_ERROR
    ];
}