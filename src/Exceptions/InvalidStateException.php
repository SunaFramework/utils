<?php declare(strict_types=1);

namespace Suna\Exceptions;

/**
 * The exception that is thrown when a method call is invalid for the object's current state,
 * the method was invoked at an illegal or inappropriate time.
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
final class InvalidStateException extends \RuntimeException
{
}