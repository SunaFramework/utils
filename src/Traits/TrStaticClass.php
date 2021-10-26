<?php declare(strict_types=1);

namespace Suna\Traits;

use Suna\Utils\Obj;

/**
 * Static Class
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
trait TrStaticClass
{

    final public function __construct()
    {
        throw new \Error('Class ' . static::class . ' is static and cannot be instantiated.');
    }

    /**
     * @param string $name
     * @param array $args
     * @throws \ReflectionException
     */
    public static function __callStatic(string $name, array $args)
    {
        Obj::strictStaticCall(static::class, $name);
    }
}