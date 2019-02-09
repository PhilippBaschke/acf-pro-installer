<?php

namespace Pivvenit\Composer\Installers\ACFPro\Tests;

use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Pivvenit\Composer\Installers\ACFPro\CopyUrlOverridingRemoteFilesystem;

class AcfProPackageProviderTest extends TestCase
{
    protected $packageProviderMock;
    protected $config;

    protected function setUp(): void
    {
        $this->packageProviderMock = $this->getMockBuilder(IOInterface::class)->getMock();
    }

    public function testExtendsComposerRemoteFilesystem()
    {
        $this->assertInstanceOf(
            \Composer\Util\RemoteFilesystem::class,
            new CopyUrlOverridingRemoteFilesystem('', $this->packageProviderMock)
        );
    }

    // Inspired by testCopy of Composer
    public function testCopyUsesAcfFileUrl()
    {
        $acfFileUrl = 'file://' . __FILE__;
        $rfs = new CopyUrlOverridingRemoteFilesystem($acfFileUrl, $this->packageProviderMock);
        $file = tempnam(sys_get_temp_dir(), 'pb');

        $this->assertTrue(
            $rfs->copy('http://example.org', 'does-not-exist', $file)
        );
        $this->assertFileExists($file);
        unlink($file);
    }
}
