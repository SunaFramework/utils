<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Exceptions\InvalidArgumentException;
use Suna\Traits\TrObject;

/**
 * DateTime Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
class DateTime extends \DateTime implements \JsonSerializable
{
    use TrObject;

    public const MINUTE = 60;

    public const HOUR = 60 * self::MINUTE;

    public const DAY = 24 * self::HOUR;

    public const WEEK = 7 * self::DAY;

    public const MONTH = 2_629_800;

    public const YEAR = 31_557_600;


    /**
     * @param string|int|\DateTimeInterface|null $time
     * @return static
     * @throws \Exception
     */
    public static function from(string|int|\DateTimeInterface|null $time): static
    {
        if ($time instanceof \DateTimeInterface) {
            return new static($time->format('Y-m-d H:i:s.u'), $time->getTimezone());
        } elseif (is_numeric($time)) {
            if ($time <= self::YEAR) {
                $time += time();
            }
            return (new static('@' . $time))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return new static((string)$time);
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param float $second
     * @return static
     * @throws \Exception
     */
    public static function fromParts(int $year, int $month, int $day, int $hour = 0, int $minute = 0, float $second = 0.0): static {
        $s = sprintf('%04d-%02d-%02d %02d:%02d:%02.5F', $year, $month, $day, $hour, $minute, $second);
        if (!checkdate($month, $day, $year) || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 | $second >= 60) {
            throw new InvalidArgumentException("Invalid date '$s'");
        }
        return new static($s);
    }

    /**
     * @param string $format
     * @param string $datetime
     * @param string|\DateTimeZone|null $timezone
     * @return false|static
     * @throws \Exception
     */
    public static function createFromFormat(string $format, string $datetime, string|\DateTimeZone $timezone = null): static|false {
        if ($timezone === null) {
            $timezone = new \DateTimeZone(date_default_timezone_get());
        } elseif (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }

        $date = parent::createFromFormat($format, $datetime, $timezone);
        return $date ? static::from($date) : false;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->format('c');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * @param string $modify
     * @return $this
     */
    public function modifyClone(string $modify = ''): static
    {
        $dolly = clone $this;
        return $modify ? $dolly->modify($modify) : $dolly;
    }
}