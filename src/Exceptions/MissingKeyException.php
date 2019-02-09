<?php namespace Pivvenit\Composer\Installers\ACFPro\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown if the ACF PRO key is not available in the environment
 */
class MissingKeyException extends Exception
{
    /**
     * Construct the MissingKeyException exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @since 5.1.0
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null) {
        parent::__construct(
            'Could not find a key for ACF PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $code,
            $previous
        );
    }
}
