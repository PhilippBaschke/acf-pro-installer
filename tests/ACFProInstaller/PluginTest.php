<?php
declare(strict_types=1);

namespace JezEmery\ACFProInstaller\Test;

use Composer\Installer\PackageEvents;
use Composer\Plugin\PluginEvents;
use JezEmery\ACFProInstaller\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    const REPO_NAME = 'advanced-custom-fields/advanced-custom-fields-pro';
    const REPO_TYPE = 'wordpress-plugin';
    const REPO_URL =
        'https://connect.advancedcustomfields.com/index.php?p=pro&a=download';
    const KEY_ENV_VARIABLE = 'ACF_PRO_KEY';

    protected function tearDown()
    {
        // Unset the environment variable after every test
        // See: http://stackoverflow.com/a/34065522
        putenv(self::KEY_ENV_VARIABLE);

        // Delete the .env file
        $dotenv = getcwd() . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($dotenv)) {
            unlink($dotenv);
        }
    }

    public function testImplementsPluginInterface()
    {
        $this->assertInstanceOf(
            'Composer\Plugin\PluginInterface',
            new Plugin()
        );
    }

    public function testImplementsEventSubscriberInterface()
    {
        $this->assertInstanceOf(
            'Composer\EventDispatcher\EventSubscriberInterface',
            new Plugin()
        );
    }

    public function testActivateMakesComposerAndIOAvailable()
    {
        $composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $plugin = new Plugin;
        $plugin->activate($composer, $io);

        $this->assertAttributeEquals($composer, 'composer', $plugin);
        $this->assertAttributeEquals($io, 'io', $plugin);
    }

    public function testSubscribesToPrePackageInstallEvent()
    {
        $subscribedEvents = Plugin::getSubscribedEvents();
        $this->assertEquals(
            $subscribedEvents[PackageEvents::PRE_PACKAGE_INSTALL],
            'addVersion'
        );
    }

    public function testSubscribesToPreUpdateInstallEvent()
    {
        $subscribedEvents = Plugin::getSubscribedEvents();
        $this->assertEquals(
            $subscribedEvents[PackageEvents::PRE_PACKAGE_UPDATE],
            'addVersion'
        );
    }

    public function testSubscribesToPreFileDownloadEvent()
    {
        $subscribedEvents = Plugin::getSubscribedEvents();
        $this->assertEquals(
            $subscribedEvents[PluginEvents::PRE_FILE_DOWNLOAD],
            'addKey'
        );
    }

    /**
     *
     */
    public function testAddVersionOnInstall()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        $package
            ->expects($this->once())
            ->method('getDistUrl')
            ->willReturn(self::REPO_URL);

        $package
            ->expects($this->once())
            ->method('setDistUrl')
            ->with(self::REPO_URL . "&t=$version");

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testAddVersionOnUpdate()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        $package
            ->expects($this->once())
            ->method('getDistUrl')
            ->willReturn(self::REPO_URL);

        $package
            ->expects($this->once())
            ->method('setDistUrl')
            ->with(self::REPO_URL . "&t=$version");

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\UpdateOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getTargetPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getTargetPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testDontAddVersionOnOtherPackages()
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn('another-package');

        $package
            ->expects($this->never())
            ->method('getPrettyVersion');

        $package
            ->expects($this->never())
            ->method('getDistUrl');

        $package
            ->expects($this->never())
            ->method('setDistUrl');

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    protected function versionPassesValidationHelper($version)
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        $package
            ->expects($this->once())
            ->method('getDistUrl')
            ->willReturn(self::REPO_URL);

        $package
            ->expects($this->once())
            ->method('setDistUrl');

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testExactVersionWith3DigitsPassesValidation()
    {
        $this->versionPassesValidationHelper('1.2.3');
    }

    public function testExactVersionWith4DigitsPassesValidation()
    {
        $this->versionPassesValidationHelper('1.2.3.4');
    }

    public function testExactVersionWithPatchDoubleDigitsPassesValidation()
    {
        $this->versionPassesValidationHelper('1.2.30');
    }

    public function testExactVersionWithPatchDoubleDigitsBetaVersionPassesValidation()
    {
        $this->versionPassesValidationHelper('1.2.30-beta3');
    }

    protected function versionFailsValidationHelper($version)
    {
        // Expect an Exception
        $this->setExpectedException(
            'UnexpectedValueException',
            'The version constraint of ' . self::REPO_NAME .
            ' should be exact (with 3 or 4 digits). ' .
            'Invalid version string "' . $version . '"'
        );

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testExactVersionWith2DigitsFailsValidation()
    {
        $this->versionFailsValidationHelper('1.2');
    }

    public function testExactVersionWith1DigitsFailsValidation()
    {
        $this->versionFailsValidationHelper('1');
    }

    public function testDontAddVersionTwice()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        $package
            ->expects($this->once())
            ->method('getDistUrl')
            ->willReturn(self::REPO_URL . '&t=' . $version);

        $package
            ->expects($this->once())
            ->method('setDistUrl')
            ->with(self::REPO_URL . "&t=$version");

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testReplaceVersionInUrl()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Package
        $package = $this
            ->getMockBuilder('Composer\Package\PackageInterface')
            ->setMethods([
                'getName',
                'getPrettyVersion',
                'getDistUrl',
                'setDistUrl'
            ])
            ->getMockForAbstractClass();

        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::REPO_NAME);

        $package
            ->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn($version);

        $package
            ->expects($this->once())
            ->method('getDistUrl')
            ->willReturn(self::REPO_URL . '&t=' . $version . '.4');

        $package
            ->expects($this->once())
            ->method('setDistUrl')
            ->with(self::REPO_URL . "&t=$version");

        // Mock an Operation
        $operationClass =
            'Composer\DependencyResolver\Operation\InstallOperation';
        $operation = $this
            ->getMockBuilder($operationClass)
            ->disableOriginalConstructor()
            ->setMethods(['getPackage'])
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        // Mock a PackageEvent
        $packageEvent = $this
            ->getMockBuilder('Composer\Installer\PackageEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getOperation'])
            ->getMock();

        $packageEvent
            ->expects($this->once())
            ->method('getOperation')
            ->willReturn($operation);

        // Call addVersion
        $plugin = new Plugin();
        $plugin->addVersion($packageEvent);
    }

    public function testAddKeyCreatesCustomFilesystemWithOldValues()
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a RemoteFilesystem
        $options = ['options' => 'array'];
        $tlsDisabled = true;

        $rfs = $this
            ->getMockBuilder('Composer\Util\HttpDownloader')
            ->disableOriginalConstructor()
            ->setMethods(['getOptions'])
            ->getMock();

        $rfs
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn($options);

        // Mock Config
        $config = $this
            ->getMockBuilder('Composer\Config')
            ->getMock();

        // Mock Composer
        $composer = $this
            ->getMockBuilder('Composer\Composer')
            ->setMethods(['getConfig'])
            ->getMock();

        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem',
                'setRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn(self::REPO_URL);

        $event
            ->expects($this->once())
            ->method('getRemoteFilesystem')
            ->willReturn($rfs);

        $event
            ->expects($this->once())
            ->method('setRemoteFilesystem')
            ->with($this->callback(
                function ($rfs) use ($config, $io, $options, $tlsDisabled) {
                    $this->assertAttributeEquals($config, 'config', $rfs);
                    $this->assertAttributeEquals($io, 'io', $rfs);
                    $this->assertEquals($options, $rfs->getOptions());
                    $this->assertEquals($tlsDisabled, $rfs->isTlsDisabled());
                    return true;
                }
            ));

        // Call addKey
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
        $plugin->addKey($event);
    }

    public function testAddKeyFromENV()
    {
        // The key that should be available in the ENVIRONMENT
        $key = 'ENV_KEY';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=' . $key);

        // Mock a RemoteFilesystem
        $rfs = $this
            ->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'isTlsDisabled'])
            ->getMock();

        $rfs
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);

        $rfs
            ->expects($this->once())
            ->method('isTlsDisabled')
            ->willReturn(true);

        // Mock Config
        $config = $this
            ->getMockBuilder('Composer\Config')
            ->getMock();

        // Mock Composer
        $composer = $this
            ->getMockBuilder('Composer\Composer')
            ->setMethods(['getConfig'])
            ->getMock();

        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem',
                'setRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn(self::REPO_URL);

        $event
            ->expects($this->once())
            ->method('getRemoteFilesystem')
            ->willReturn($rfs);

        $event
            ->expects($this->once())
            ->method('setRemoteFilesystem')
            ->with($this->callback(
                function ($rfs) use ($key) {
                    $this->assertAttributeContains(
                        "&k=$key",
                        'acfFileUrl',
                        $rfs
                    );
                    return true;
                }
            ));

        // Call addKey
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
        $plugin->addKey($event);
    }

    public function testAddKeyFromDotEnv()
    {
        // The key that should be available in the .env file
        $key = 'DOT_ENV_KEY';

        // Make key available in the .env file
        file_put_contents(
            getcwd() . DIRECTORY_SEPARATOR . '.env',
            self::KEY_ENV_VARIABLE . '=' . $key
        );

        // Mock a RemoteFilesystem
        $rfs = $this
            ->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'isTlsDisabled'])
            ->getMock();

        $rfs
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);

        $rfs
            ->expects($this->once())
            ->method('isTlsDisabled')
            ->willReturn(true);

        // Mock Config
        $config = $this
            ->getMockBuilder('Composer\Config')
            ->getMock();

        // Mock Composer
        $composer = $this
            ->getMockBuilder('Composer\Composer')
            ->setMethods(['getConfig'])
            ->getMock();

        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem',
                'setRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn(self::REPO_URL);

        $event
            ->expects($this->once())
            ->method('getRemoteFilesystem')
            ->willReturn($rfs);

        $event
            ->expects($this->once())
            ->method('setRemoteFilesystem')
            ->with($this->callback(
                function ($rfs) use ($key) {
                    $this->assertAttributeContains(
                        "&k=$key",
                        'acfFileUrl',
                        $rfs
                    );
                    return true;
                }
            ));

        // Call addKey
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
        $plugin->addKey($event);
    }

    public function testPreferKeyFromEnv()
    {
        // The key that should be available in the .env file
        $fileKey = 'DOT_ENV_KEY';
        $key = 'ENV_KEY';

        // Make key available in the .env file
        file_put_contents(
            getcwd() . DIRECTORY_SEPARATOR . '.env',
            self::KEY_ENV_VARIABLE . '=' . $fileKey
        );

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=' . $key);

        // Mock a RemoteFilesystem
        $rfs = $this
            ->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'isTlsDisabled'])
            ->getMock();

        $rfs
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);

        $rfs
            ->expects($this->once())
            ->method('isTlsDisabled')
            ->willReturn(true);

        // Mock Config
        $config = $this
            ->getMockBuilder('Composer\Config')
            ->getMock();

        // Mock Composer
        $composer = $this
            ->getMockBuilder('Composer\Composer')
            ->setMethods(['getConfig'])
            ->getMock();

        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem',
                'setRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn(self::REPO_URL);

        $event
            ->expects($this->once())
            ->method('getRemoteFilesystem')
            ->willReturn($rfs);

        $event
            ->expects($this->once())
            ->method('setRemoteFilesystem')
            ->with($this->callback(
                function ($rfs) use ($key) {
                    $this->assertAttributeContains(
                        "&k=$key",
                        'acfFileUrl',
                        $rfs
                    );
                    return true;
                }
            ));

        // Call addKey
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
        $plugin->addKey($event);
    }

    public function testThrowExceptionWhenKeyIsMissing()
    {
        // Expect an Exception
        $this->setExpectedException(
            'PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyException',
            'ACF_PRO_KEY'
        );

        // Mock a RemoteFilesystem
        $rfs = $this
            ->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn(self::REPO_URL);

        $event
            ->expects($this->once())
            ->method('getRemoteFilesystem')
            ->willReturn($rfs);

        // Call addKey
        $plugin = new Plugin();
        $plugin->addKey($event);
    }

    public function testOnlyAddKeyOnAcfUrl()
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock an Event
        $event = $this
            ->getMockBuilder('Composer\Plugin\PreFileDownloadEvent')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProcessedUrl',
                'getRemoteFilesystem',
                'setRemoteFilesystem'
            ])
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getProcessedUrl')
            ->willReturn('another-url');

        $event
            ->expects($this->never())
            ->method('getRemoteFilesystem');

        $event
            ->expects($this->never())
            ->method('setRemoteFilesystem');

        // Call addKey
        $plugin = new Plugin();
        $plugin->addKey($event);
    }
}
