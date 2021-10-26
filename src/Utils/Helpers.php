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

}