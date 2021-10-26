<?php declare(strict_types=1);

namespace Suna\Utils;

/**
 * Callback Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Callback
{
    /**
     * @param \Closure $closure
     * @return callable
     * @throws \ReflectionException
     */
    public static function unwrap(\Closure $closure): callable
    {
        $refFunction = new \ReflectionFunction($closure);
        if (str_ends_with($refFunction->name, '}')) {
            return $closure;
        } elseif ($obj = $refFunction->getClosureThis()) {
            return [$obj, $refFunction->name];
        } elseif ($class = $refFunction->getClosureScopeClass()) {
            return [$class->name, $refFunction->name];
        }

        return $refFunction->name;
    }

    /**
     * @param callable $callable
     * @return bool
     */
    public static function isStatic(callable $callable): bool
    {
        return is_array($callable) ? is_string($callable[0]) : is_string($callable);
    }

    /**
     * Returns the Reflection Obj to the method or function used in the PHP callback.
     *
     * @param $callable
     * @return \ReflectionFunctionAbstract
     * @throws \ReflectionException
     */
    public static function toReflection($callable): \ReflectionFunctionAbstract
    {
        if ($callable instanceof \Closure) {
            $callable = self::unwrap($callable);
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            return new \ReflectionMethod($callable);
        } elseif (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !$callable instanceof \Closure) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        return new \ReflectionFunction($callable);
    }

    /**
     * @param string $function
     * @param array $args
     * @param callable $onError
     * @return mixed
     */
    public static function invokeSafe(string $function, array $args, callable $onError): mixed
    {
        $prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function): ?bool {
            if ($file === __FILE__) {
                $message = ini_get('html_errors') ? Html::toText($message) : $message;
                $message = preg_replace("#^$function\\(.*?\\): #", '', $message);
                if ($onError($message, $severity) !== false) {
                    return null;
                }
            }
            return $prev ? $prev(...func_get_args()) : false;
        });

        try {
            return $function(...$args);
        } finally {
            restore_error_handler();
        }
    }

}