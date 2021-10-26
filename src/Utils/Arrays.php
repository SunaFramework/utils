<?php declare(strict_types=1);

namespace Suna\Utils;

/**
 * Callback Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Arrays
{
    /**
     * Checks if an array contains a certain element
     *
     * @param array $array
     * @param mixed $value
     * @return bool
     */
    public static function contains(array $array, mixed $value): bool
    {
        return in_array($value, $array, true);
    }

    /**
     * Returns the first item from the array or null if array is empty.
     * @note The original array is not altered
     *
     * @param array $array
     * @return mixed
     */
    public static function first(array $array): mixed
    {
        $arrCopy = $array;
        return array_shift($arrCopy);
    }

}