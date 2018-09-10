<?php namespace PhilippBaschke\ACFProInstaller\Test\Exceptions;

use PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyFromComposerExtraException;

class MissingKeyFromComposerExtraExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $message = 'FIELD';
        $e = new MissingKeyFromComposerExtraException($message);
        $this->assertEquals(
            'Could not find a key for ACF PRO. ' .
            'Please make it available via the composer extra section\'s ' .
            $message,
            $e->getMessage()
        );
    }
}
