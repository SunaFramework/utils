<?php declare(strict_types=1);

namespace Suna\Traits;

use Suna\Utils\Exceptions\MemberAccessException;
use Suna\Utils\Exceptions\UnexpectedValueException;
use Suna\Utils\Obj;

/**
 * Strict class for a better experience.
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
trait TrObject
{
    /**
     * @param string $name
     * @param array $args
     * @return void|null
     * @throws \ReflectionException
     */
    public function __call(string $name, array $args)
    {
        $class = static::class;

        if (Obj::hasProperty($class, $name) === 'event') {
            $handlers = $this->$name ?? null;
            if ($handlers !== null && !is_iterable($handlers)) {
                throw new UnexpectedValueException("Property $class::$$name must be iterable or null, " . gettype($handlers) . ' given.');
            }
            foreach ($handlers as $handler) {
                $handler(...$args);
            }
            return null;
        }

        Obj::strictCall($class, $name);
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    public static function __callStatic(string $name, array $args)
    {
        Obj::strictStaticCall(static::class, $name);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \ReflectionException
     */
    public function &__get(string $name)
    {
        $class = static::class;

        if ($prop = Obj::getMagicProperties($class)[$name] ?? null) {
            if (!($prop & 0b0001)) {
                throw new MemberAccessException("Cannot read a write-only property $class::\$$name.");
            }
            $m = ($prop & 0b0010 ? 'get' : 'is') . $name;
            return $this->$m();
        }
        Obj::strictGet($class, $name);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \ReflectionException
     */
    public function __set(string $name, mixed $value): void
    {
        $class = static::class;

        if (Obj::hasProperty($class, $name)) {
            $this->$name = $value;
            return;
        } elseif ($prop = Obj::getMagicProperties($class)[$name] ?? null) {
            if (!($prop & 0b1000)) {
                throw new MemberAccessException("Cannot write to a read-only property $class::\$$name.");
            }
            $this->{'set' . $name}($value);
            return;
        }
        Obj::strictSet($class, $name);
    }

    /**
     * @param string $name
     */
    public function __unset(string $name): void
    {
        $class = static::class;
        if (!Obj::hasProperty($class, $name)) {
            throw new MemberAccessException("Cannot unset the property $class::\$$name.");
        }
    }

    /**
     * @param string $name
     * @return bool
     * @throws \ReflectionException
     */
    public function __isset(string $name): bool
    {
        return isset(Obj::getMagicProperties(static::class)[$name]);
    }
}