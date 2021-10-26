<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Traits\TrStaticClass;
use Suna\Exceptions\MemberAccessException;

/**
 * Object Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Obj
{
    use TrStaticClass;

    /**
     * @param string $class
     * @param string $name
     * @throws \ReflectionException
     */
    public static function strictGet(string $class, string $name): void
    {
        $rc = new \ReflectionClass($class);
        $hint = self::getSuggestion(array_merge(
            array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), fn($p) => !$p->isStatic()),
            self::parseFullDoc($rc, '~^[ \t*]*@property(?:-read)?[ \t]+(?:\S+[ \t]+)??\$(\w+)~m'),
        ), $name);
        throw new MemberAccessException("Cannot read an undeclared property $class::\$$name" . ($hint ? ", did you mean \$$hint?" : '.'));
    }

    /**
     * @param string $class
     * @param string $name
     * @throws \ReflectionException
     */
    public static function strictSet(string $class, string $name): void
    {
        $rc = new \ReflectionClass($class);
        $hint = self::getSuggestion(array_merge(
            array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), fn($p) => !$p->isStatic()),
            self::parseFullDoc($rc, '~^[ \t*]*@property(?:-write)?[ \t]+(?:\S+[ \t]+)??\$(\w+)~m'),
        ), $name);
        throw new MemberAccessException("Cannot write to an undeclared property $class::\$$name" . ($hint ? ", did you mean \$$hint?" : '.'));
    }

    /**
     * @param mixed $context
     * @param string $class
     * @param string $method
     * @return false|string
     */
    private static function getClass(mixed $context, string $class, string $method): string|false
    {
        if ($context && is_a($class, $context, true) && method_exists($context, $method)) { // called parent::$method()
            $class = get_parent_class($context);
        }

        if (method_exists($class, $method)) {
            $rm = new \ReflectionMethod($class, $method);
            $visibility = $rm->isPrivate() ? 'private ' : ($rm->isProtected() ? 'protected ' : '');
            throw new MemberAccessException("Call to {$visibility}method $class::$method() from " . ($context ? "scope $context." : 'global scope.'));
        }
        return $class;
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $additionalMethods
     * @throws \ReflectionException
     */
    public static function strictCall(string $class, string $method, array $additionalMethods = []): void
    {
        $trace = debug_backtrace(0, 3);
        $context = ($trace[1]['function'] ?? null) === '__call' ? ($trace[2]['class'] ?? null) : null;

        $class = self::getClass($context, $class, $method);
        $hint = self::getSuggestion(array_merge(
            get_class_methods($class),
            self::parseFullDoc(new \ReflectionClass($class), '~^[ \t*]*@method[ \t]+(?:\S+[ \t]+)??(\w+)\(~m'),
            $additionalMethods,
        ), $method);

        throw new MemberAccessException("Call to undefined method $class::$method()" . ($hint ? ", did you mean $hint()?" : '.'));
    }

    /**
     * @param string $class
     * @param string $method
     * @throws \ReflectionException
     */
    public static function strictStaticCall(string $class, string $method): void
    {
        $trace = debug_backtrace(0, 3);
        $context = ($trace[1]['function'] ?? null) === '__callStatic' ? ($trace[2]['class'] ?? null) : null;

        $class = self::getClass($context, $class, $method);
        $hint = self::getSuggestion(
            array_filter((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC), fn($m) => $m->isStatic()),
            $method,
        );
        throw new MemberAccessException("Call to undefined static method $class::$method()" . ($hint ? ", did you mean $hint()?" : '.'));
    }

    /**
     * @param string $class
     * @return array
     * @throws \ReflectionException
     */
    public static function getMagicProperties(string $class): array
    {
        static $cache;
        $props = &$cache[$class];
        if ($props !== null) {
            return $props;
        }

        $rc = new \ReflectionClass($class);

        $pattern = '~^  [ \t*]*  @property(|-read|-write)  [ \t]+  [^\s$]+  [ \t]+  \$  (\w+)  ()~mx';
        preg_match_all($pattern, (string) $rc->getDocComment(), $matches, PREG_SET_ORDER);

        $props = [];
        foreach ($matches as [, $type, $name]) {
            $uname = ucfirst($name);
            $write = $type !== '-read' && $rc->hasMethod($nm = 'set' . $uname) && ($rm = $rc->getMethod($nm))->name === $nm && !$rm->isPrivate() && !$rm->isStatic();
            $read = $type !== '-write' && ($rc->hasMethod($nm = 'get' . $uname) || $rc->hasMethod($nm = 'is' . $uname)) && ($rm = $rc->getMethod($nm))->name === $nm && !$rm->isPrivate() && !$rm->isStatic();

            if ($read || $write) {
                $props[$name] = $read << 0 | ($nm[0] === 'g') << 1 | $rm->returnsReference() << 2 | $write << 3;
            }
        }

        foreach ($rc->getTraits() as $trait) {
            $props += self::getMagicProperties($trait->name);
        }

        if ($parent = get_parent_class($class)) {
            $props += self::getMagicProperties($parent);
        }
        return $props;
    }

    /**
     * @param array $possibilities
     * @param string $value
     * @return string|null
     */
    public static function getSuggestion(array $possibilities, string $value): ?string
    {
        $norm = preg_replace($re = '#^(get|set|has|is|add)(?=[A-Z])#', '+', $value);
        $best = null;
        $min = (strlen($value) / 4 + 1) * 10 + .1;
        foreach (array_unique($possibilities, SORT_REGULAR) as $item) {
            $item = $item instanceof \Reflector ? $item->name : $item;
            if ($item !== $value && (
                    ($len = levenshtein($item, $value, 10, 11, 10)) < $min
                    || ($len = levenshtein(preg_replace($re, '*', $item), $norm, 10, 11, 10)) < $min
                )) {
                $min = $len;
                $best = $item;
            }
        }
        return $best;
    }

    /**
     * @param \ReflectionClass $rc
     * @param string $pattern
     * @return array
     */
    private static function parseFullDoc(\ReflectionClass $rc, string $pattern): array
    {
        do {
            $doc[] = $rc->getDocComment();
            $traits = $rc->getTraits();
            while ($trait = array_pop($traits)) {
                $doc[] = $trait->getDocComment();
                $traits += $trait->getTraits();
            }
        } while ($rc = $rc->getParentClass());

        return preg_match_all($pattern, implode('', $doc), $m) ? $m[1] : [];
    }

    /**
     * @param string $class
     * @param string $name
     * @return bool|string
     */
    public static function hasProperty(string $class, string $name): bool|string
    {
        static $cache;
        $prop = &$cache[$class][$name];
        if ($prop === null) {
            $prop = false;
            try {
                $rp = new \ReflectionProperty($class, $name);
                if ($rp->isPublic() && !$rp->isStatic()) {
                    $prop = $name >= 'onA' && $name < 'on_' ? 'event' : true;
                }
            } catch (\ReflectionException $e) {
            }
        }
        return $prop;
    }
}