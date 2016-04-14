<?php namespace PhilippBaschke\ACFProInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * A composer plugin that adds a repository for ACF PRO
 *
 * The WordPress plugin Advanced Custom Fields PRO (ACF PRO) does not
 * offer a way to install it via composer natively.
 *
 * This plugin adds a 'package' repository to composer that downloads the
 * correct version from the ACF site using the version number from
 * composer.json and a license key from the ENVIRONMENT or an .env file.
 *
 * With this plugin user no longer need to supply the repository and expose
 * their license key in composer.json.
 */
class Plugin implements PluginInterface
{
    /**
     * Path to file that contains the repository definition for ACF PRO
     *
     * This file contains a repository definition that would normally be used
     * in the repositories attribute in composer.json.
     * @url https://getcomposer.org/doc/04-schema.md#repositories
     *
     * It is based on the recommended approach from the ACF support forum.
     * @url https://gist.github.com/dmalatesta/4fae4490caef712a51bf
     *
     * @access protected
     * @var string
     */
    protected $repositoryFile;

    /**
     * Constructor
     *
     * Set up the path to the repository file when the Plugin is created.
     */
    public function __construct()
    {
        $this->repositoryFile = __DIR__.DIRECTORY_SEPARATOR.'repository.json';
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $config = json_decode(file_get_contents($this->repositoryFile), true);
        $requiredVersion = $this->getVersion(
            $config['package']['name'],
            $composer->getPackage()
        );

        if (!$requiredVersion) {
            return;
        }

        $config['package']['version'] = $requiredVersion;
        $repository = $composer->getRepositoryManager()
                    ->createRepository($config['type'], $config);
        $composer->getRepositoryManager()->prependRepository($repository);
    }

    /**
     * Get the required version of a package from the root package
     *
     * This function will extract the required version from a package
     * definition in composer.json.
     *
     * E.g: "test/test": "1.2.3" in composer.json => 1.2.3
     *
     * @access protected
     * @param string $package The name of the package
     * @param Composer\Package\RootPackageInterface A composer root package
     * @return mixed
     *   The version of the package from the required packages (if defined) or
     *   the version of the package from the require-dev packages (if defined).
     *   false otherwise
     * @todo
     *   Consider adding a case when the package is defined in require and
     *   require-dev (currently returns version from require).
     */
    protected function getVersion($package, $rootPackage)
    {
        $require = $rootPackage->getRequires();
        $requireDev = $rootPackage->getDevRequires();

        if (isset($require[$package])) {
            return $require[$package]->getPrettyConstraint();
        } elseif (isset($requireDev[$package])) {
            return $requireDev[$package]->getPrettyConstraint();
        }
        return false;
    }
}
