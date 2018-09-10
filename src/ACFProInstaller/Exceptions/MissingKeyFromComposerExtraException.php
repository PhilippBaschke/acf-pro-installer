<?php namespace PhilippBaschke\ACFProInstaller\Exceptions;

/**
 * Exception thrown if the ACF PRO key is not available in the composer extra section.
 */
class MissingKeyFromComposerExtraException extends MissingKeyException
{
    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct(
            'Could not find a key for ACF PRO. ' .
            'Please make it available via the composer extra section\'s ' .
            $message,
            $code,
            $previous
        );
    }
}
