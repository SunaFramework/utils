<?php

namespace Suna\Exceptions;

/**
 * The exception that is thrown when a method invoked is not supported.
 * For scenarios where it is sometimes possible to perform the requested operation, see InvalidStateException.
 *
 * @version 0.1
 * @author Filipe Voges <filipe.vogesh@gmail.com>
 */
class NotSupportedException extends \LogicException
{
}