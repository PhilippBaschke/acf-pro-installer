<?php
declare(strict_types=1);

namespace JezEmery\ACFProInstaller\Test\Exceptions;

use JezEmery\ACFProInstaller\Exceptions\MissingKeyException;

class MissingKeyExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $message = 'FIELD';
        $e = new MissingKeyException($message);
        $this->assertEquals(
            'Could not find a key for ACF PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $e->getMessage()
        );
    }
}
