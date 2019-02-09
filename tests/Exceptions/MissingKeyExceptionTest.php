<?php

namespace Pivvenit\Composer\Installers\ACFPro\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Pivvenit\Composer\Installers\ACFPro\Exceptions\MissingKeyException;

final class MissingKeyExceptionTest extends TestCase
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
