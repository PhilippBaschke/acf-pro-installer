<?php namespace PhilippBaschke\ACFProInstaller\Test;

use PhilippBaschke\ACFProInstaller\RemoteFilesystem;

class RemoteFilesystemTest extends \PHPUnit_Framework_TestCase
{
    protected $io;
    protected $config;

    protected function setUp()
    {
        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $this->config = $this->getMockBuilder('Composer\Config')->getMock();
    }

    public function testExtendsComposerRemoteFilesystem()
    {
        $this->assertInstanceOf(
            'Composer\Util\RemoteFilesystem',
            new RemoteFilesystem('', $this->io, $this->config)
        );
    }

    public function testCopyUsesAcfFileUrl()
    {
        $acfFileUrl = 'acfFileUrl';

        // Expect an Exception
        $this->setExpectedException(
            'Composer\Downloader\TransportException',
            $acfFileUrl
        );

        $rfs = new RemoteFilesystem($acfFileUrl, $this->io, $this->config);
        $rfs->copy('orginUrl', 'fileUrl', 'fileName');
    }
}
