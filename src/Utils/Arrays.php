<?php declare(strict_types=1);

namespace Suna\Utils;

/**
 * Arrays Helpers
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

    /**
     * Returns the last item from the array or null if array is empty.
     * @note The original array is not altered
     *
     * @param array $array
     * @return mixed
     */
    public static function last(array $array): mixed
    {
        $arrCopy = $array;
        return end($arrCopy);
    }

    /**
     * Returns the min or max value of an array
     *
     * @param array $arr
     * @param bool $max
     * @param bool $assoc
     * @param string $keyVal
     * @param callable|null $filter
     * @return int
     *
     * @author Filipe Voges <filipe.vogesh@gmail.com>
     * @since 2021-10-26
     */
    public static function minMax(array $arr, bool $max = false, bool $assoc = false, string $keyVal = '', callable $filter = NULL): int
    {
        if (!is_null($filter)) {
            $arr = array_filter($arr, $filter);
        }

        if($assoc) {
            $arr = array_map(function ($el) use ($max, $keyVal) {
                return (isset($el[$keyVal]) && is_numeric($el[$keyVal])) ? $el[$keyVal] : 0;
            }, $arr);
        }
        if(!count($arr)) return 0;

        $arr = array_unique($arr);
        if ($max) {
            return max($arr);
        }
        return min($arr);
    }

    /**
     * Convert a value to array key
     *
     * @param mixed $value
     * @return int|string
     */
    public static function toKey(mixed $value): int|string
    {
        return key([$value => null]);
    }

    /**
     * Returns the zero-indicated position of the given array key.
     * @note Returns null if key is not found.
     *
     * @param array $array
     * @param string|int $key
     * @return int|null
     */
    public static function getKeyOffset(array $array, string|int $key): ?int
    {
        return Helpers::falseToNull(array_search(self::toKey($key), array_keys($array), true));
    }

    /**
     * Inserts the contents of $inserted into the $array before $key.
     * @note If $key is null (or doesn't exist), it will be inserted at the beginning.
     *
     * @param array $array
     * @param string|int|null $key
     * @param array $inserted
     */
    public static function insertBefore(array &$array, string|int|null $key, array $inserted): void
    {
        $offset = $key === null ? 0 : (int) self::getKeyOffset($array, $key);
        $array = array_slice($array, 0, $offset, true) + $inserted + array_slice($array, $offset, count($array), true);
    }


    /**
     * Inserts the contents of $inserted into the $array after $key.
     * @note If $key is null (or doesn't exist), it will be inserted at the end.
     *
     * @param array $array
     * @param string|int|null $key
     * @param array $inserted
     */
    public static function insertAfter(array &$array, string|int|null $key, array $inserted): void
    {
        if ($key === null || ($offset = self::getKeyOffset($array, $key)) === null) {
            $offset = count($array) - 1;
        }
        $array = array_slice($array, 0, $offset + 1, true) + $inserted + array_slice($array, $offset + 1, count($array), true);
    }

    /**
     * @param array|string $search
     * @param array|string $replace
     * @param array|string $subject
     * @param $count
     * @return mixed
     */
    function strReplace(array|string $search, array|string $replace, array|string $subject, $count): mixed
    {
        return json_decode(str_replace($search, $replace,  json_encode($subject)), $count);

    }

}