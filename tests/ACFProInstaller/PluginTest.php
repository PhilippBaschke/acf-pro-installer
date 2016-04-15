<?php namespace PhilippBaschke\ACFProInstaller\Test;

use PhilippBaschke\ACFProInstaller\Plugin;

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
        $dotenv = getcwd().DIRECTORY_SEPARATOR.'.env';
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

    public function testCreatePackageRepository()
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->equalTo('package'),
                $this->callback(function ($config) {
                    if (!isset($config['package'])) {
                        return false;
                    }

                    $package = $config['package'];
                    return
                    $package['name'] === self::REPO_NAME &&
                        array_key_exists('version', $package) &&
                    $package['type'] === self::REPO_TYPE &&
                    $package['dist']['type'] === 'zip' &&
                    strpos($package['dist']['url'], self::REPO_URL) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testPrependRepository()
    {
        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods([
                               'createRepository',
                               'prependRepository'
                           ])
                           ->getMock();

        $repositoryManager
            ->expects($this->any())
            ->method('createRepository')
            ->willReturn($repositoryInterface);

        $repositoryManager
            ->expects($this->once())
            ->method('prependRepository')
            ->with($this->identicalTo($repositoryInterface));

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->any())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testDontCreateRepositoryWhenNotRequired()
    {
        // Mock a RootPackageInterface (returned by getPackage)
        // Not mocking getRequires/getDevRequires is like the package
        // is not required (because the methods return null)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->getMockForAbstractClass();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->never())
            ->method('createRepository');

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testGetVersionFromRequires()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (returned by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->setMethods(['getPrettyConstraint'])
              ->getMock();

        $link
            ->expects($this->once())
            ->method('getPrettyConstraint')
            ->willReturn($version);

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($version) {
                    return $config['package']['version'] === $version;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testGetVersionFromDevRequires()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (returned by getDevRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->setMethods(['getPrettyConstraint'])
              ->getMock();

        $link
            ->expects($this->once())
            ->method('getPrettyConstraint')
            ->willReturn($version);

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getDevRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getDevRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($version) {
                    return $config['package']['version'] === $version;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testExactVersionPassesValidation()
    {
        // The version that should be required
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (returned by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->setMethods(['getPrettyConstraint'])
              ->getMock();

        $link
            ->method('getPrettyConstraint')
            ->willReturn($version);

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->willReturn($repositoryInterface);

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    protected function versionFailsValidationHelper($version)
    {
        // Expect an Exception
        $this->setExpectedException(
            'UnexpectedValueException',
            'The version constraint of ' . self::REPO_NAME .
            ' should be exact (with 3 digits). ' .
            'Invalid version string "' . $version . '"'
        );

        // Mock a Link (returned by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->setMethods(['getPrettyConstraint'])
              ->getMock();

        $link
            ->method('getPrettyConstraint')
            ->willReturn($version);

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer->expects($this->never())->method('getRepositoryManager');
        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testRangeVersionFailsValidation()
    {
        $this->versionFailsValidationHelper('>=1.0');
    }

    public function testRangeHyphenVersionFailsValidation()
    {
        $this->versionFailsValidationHelper('1.0 - 2.0');
    }

    public function testWildcardVersionFailsValidation()
    {
        $this->versionFailsValidationHelper('1.0.*');
    }

    public function testTildeVersionFailsValidation()
    {
        $this->versionFailsValidationHelper('~1.2.3');
    }

    public function testCaretVersionFailsValidation()
    {
        $this->versionFailsValidationHelper('^1.2.3');
    }

    public function testExactVersionWithout3DigitsFailsValidation()
    {
        $this->versionFailsValidationHelper('1.2');
    }

    public function testAddVersionToDistUrl()
    {
        // The version that should be added to the url
        $version = '1.2.3';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=KEY');

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn($version);

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($version) {
                    return strpos(
                        $config['package']['dist']['url'],
                        '&t='.$version
                    ) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testAddKeyFromENVToDistUrl()
    {
        // The key that should be available in the ENVIRONMENT
        $key = 'ENV_KEY';

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=' . $key);

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($key) {
                    return strpos(
                        $config['package']['dist']['url'],
                        '&k='.$key
                    ) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testAddKeyFromDotEnvToDistUrl()
    {
        // The key that should be available in the .env file
        $key = 'DOT_ENV_KEY';

        // Make key available in the .env file
        file_put_contents(
            getcwd().DIRECTORY_SEPARATOR.'.env',
            self::KEY_ENV_VARIABLE . '=' . $key
        );

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($key) {
                    return strpos(
                        $config['package']['dist']['url'],
                        '&k='.$key
                    ) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testPreferKeyFromEnv()
    {
        // The key that should be available in the .env file
        $fileKey = 'DOT_ENV_KEY';
        $key = 'ENV_KEY';

        // Make key available in the .env file
        file_put_contents(
            getcwd().DIRECTORY_SEPARATOR.'.env',
            self::KEY_ENV_VARIABLE . '=' . $fileKey
        );

        // Make key available in the ENVIRONMENT
        putenv(self::KEY_ENV_VARIABLE . '=' . $key);

        // Mock a Link (return by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->getMock();

        $link->method('getPrettyConstraint')->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock a RepositoryInterface (returned by createRepository)
        $repositoryInterface = $this
                             ->getMockBuilder(
                                 'Composer\Repository\RepositoryInterface'
                             )
                             ->getMock();

        // Mock a RepositoryManager
        $repositoryManager = $this
                           ->getMockBuilder(
                               'Composer\Repository\RepositoryManager'
                           )
                           ->disableOriginalConstructor()
                           ->setMethods(['createRepository'])
                           ->getMock();

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with(
                $this->anything(),
                $this->callback(function ($config) use ($key) {
                    return strpos(
                        $config['package']['dist']['url'],
                        '&k='.$key
                    ) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }

    public function testThrowExceptionWhenKeyIsMissing()
    {
        // Expect an Exception
        $this->setExpectedException(
            'PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyException',
            'ACF_PRO_KEY'
        );

        // Mock a Link (returned by getRequires)
        $link = $this->getMockBuilder('Composer\Package\Link')
              ->disableOriginalConstructor()
              ->setMethods(['getPrettyConstraint'])
              ->getMock();

        $link
            ->method('getPrettyConstraint')
            ->willReturn('1.2.3');

        // Mock a RootPackageInterface (returned by getPackage)
        $rootPackageInterface = $this
                              ->getMockBuilder(
                                  'Composer\Package\RootPackageInterface'
                              )
                              ->setMethods(['getRequires'])
                              ->getMockForAbstractClass();

        $rootPackageInterface
            ->method('getRequires')
            ->willReturn([self::REPO_NAME => $link]);

        // Mock Composer
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager', 'getPackage'])
                  ->getMock();

        $composer->expects($this->never())->method('getRepositoryManager');
        $composer->method('getPackage')->willReturn($rootPackageInterface);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }
}
