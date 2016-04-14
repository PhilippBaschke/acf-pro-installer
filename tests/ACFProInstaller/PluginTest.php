<?php namespace PhilippBaschke\ACFProInstaller\Test;

use PhilippBaschke\ACFProInstaller\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    const REPO_NAME = 'advanced-custom-fields/advanced-custom-fields-pro';
    const REPO_TYPE = 'wordpress-plugin';
    const REPO_URL =
      'https://connect.advancedcustomfields.com/index.php?p=pro&a=download';

    public function testImplementsPluginInterface()
    {
        $this->assertInstanceOf(
            'Composer\Plugin\PluginInterface',
            new Plugin()
        );
    }

    public function testCreatePackageRepository()
    {
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
                    isset($package['version']) &&
                    $package['type'] === self::REPO_TYPE &&
                    $package['dist']['type'] === 'zip' &&
                    strpos($package['dist']['url'], self::REPO_URL) !== false;
                })
            )
            ->willReturn($repositoryInterface);

        // Mock Composer (returns the mocked RepositoryManager)
        $composer = $this
                  ->getMockBuilder('Composer\Composer')
                  ->setMethods(['getRepositoryManager'])
                  ->getMock();

        $composer
            ->expects($this->atLeast(1))
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

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
                  ->setMethods(['getRepositoryManager'])
                  ->getMock();

        $composer
            ->expects($this->any())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        // Mock IOInterface
        $io = $this
            ->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();

        // Activate Plugin
        $plugin = new Plugin();
        $plugin->activate($composer, $io);
    }
}
