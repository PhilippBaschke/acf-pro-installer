<?php
declare(strict_types=1);

namespace JezEmery\ACFProInstaller\Test;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\HttpDownloader;

class RemoteFilesystemTest extends \PHPUnit_Framework_TestCase
{
    protected $io;
    protected $config;

    protected function setUp()
    {
        $this->io = $this->getMockBuilder(IOInterface::class)->getMock();
    }

    public function testExtendsComposerRemoteFilesystem()
    {
        $this->assertInstanceOf(
            'Composer\Util\RemoteFilesystem',
            new RemoteFilesystem('', $this->io)
        );
    }

    // Inspired by testCopy of Composer
    public function testCopyUsesAcfFileUrl()
    {
        $config = $this->getMockBuilder(Config::class)->getMock();


        $composer = $this->getMockBuilder(Composer::class)->setMethods(['getConfig'])->getMock();

        $composer->expects($this->once())->method('getConfig')->willReturn($config);

        $acfFileUrl = 'file://' . __FILE__;

        $rfs = new HttpDownloader($this->io, $composer->getConfig());
        $rfs->get($acfFileUrl);

        $file = tempnam(sys_get_temp_dir(), 'pb');

        $this->assertTrue(
            $rfs->copy('http://example.org', 'does-not-exist', $file)
        );

        $this->assertFileExists($file);
        unlink($file);
    }
}
