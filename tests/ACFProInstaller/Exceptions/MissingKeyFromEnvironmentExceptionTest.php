<?php namespace PhilippBaschke\ACFProInstaller\Test\Exceptions;

use PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyFromEnvironmentException;

class MissingKeyFromEnvironmentExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $message = 'FIELD';
        $e = new MissingKeyFromEnvironmentException($message);
        $this->assertEquals(
            'Could not find a key for ACF PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $e->getMessage()
        );
    }
}
