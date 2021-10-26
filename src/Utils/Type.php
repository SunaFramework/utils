<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Utils\Exceptions\InvalidArgumentException;

/**
 * Type Helpers
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class Type
{
    /**
     * @var string
     */
    static string $pattern = '#(?:\?[\w\\\\]+|[\w\\\\]+ (?: (&[\w\\\\]+)* | (\|[\w\\\\]+)* ))()$#xAD';

    /**
     * @param string|null $type
     * @param bool $nullable
     * @return string|null
     */
    public static function validate(?string $type, bool &$nullable): ?string
    {
        if ($type === '' || $type === null) {
            return  null;
        }

        if (!preg_match(self::$pattern, $type)) {
            throw new InvalidArgumentException("The value '$type' is not a valid type.");
        }
        if ($type[0] === '?') {
            $nullable = true;
            return substr($type, 1);
        }
        return $type;
    }

    /**
     * @param string $type
     * @return static
     */
    public static function fromString(string $type): self
    {
        if (!preg_match(self::$pattern, $type, $m)) {
            throw new InvalidArgumentException("Invalid type '$type'.");
        }
        [, $nType, $iType] = $m;

        if ($nType) {
            return new Type([$nType, 'null']);
        } elseif ($iType) {
            return new Type(explode('&', $type), '&');
        }

        return new Type(explode('|', $type));
    }

    /**
     * @var array
     */
    private array $types;

    /**
     * @var bool
     */
    private bool $single;

    /**
     * @var string
     */
    private string $kind; // | &

    /**
     * @param array $types
     * @param string $kind
     */
    private function __construct(array $types, string $kind = '|')
    {
        if ($types[0] === 'null') {
            array_push($types, array_shift($types));
        }
        $this->types = $types;
        $this->single = ($types[1] ?? 'null') === 'null';
        $this->kind = sizeof($types) > 1 ? $kind : '';
    }

}