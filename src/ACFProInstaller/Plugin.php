<?php namespace PhilippBaschke\ACFProInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Dotenv\Dotenv;
use PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyException;

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
     * The name of the environment variable
     * where the ACF PRO key should be stored.
     */
    const KEY_ENV_VARIABLE = 'ACF_PRO_KEY';

    /**
     * The repository definition for ACF PRO
     *
     * The definition is loaded from repository.json.
     * This file contains a repository definition that would normally be used
     * in the repositories attribute in composer.json.
     * @link https://getcomposer.org/doc/04-schema.md#repositories
     *
     * It is based on the recommended approach from the ACF support forum.
     * @link https://gist.github.com/dmalatesta/4fae4490caef712a51bf
     *
     * @access protected
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * Load the repository file when the Plugin is created.
     */
    public function __construct()
    {
        $repositoryFile = __DIR__.DIRECTORY_SEPARATOR.'repository.json';
        $this->config = json_decode(file_get_contents($repositoryFile), true);
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $requiredVersion = $this->getVersion($composer->getPackage());
        if (!$requiredVersion) {
            return;
        }
        $key = $this->getKeyFromEnv();

        $this->updateConfig($requiredVersion, $key);

        $repository = $composer->getRepositoryManager()
                    ->createRepository($this->config['type'], $this->config);
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
     * @param Composer\Package\RootPackageInterface
     *   $rootPackage A composer root package
     * @return mixed
     *   The version of the package from the required packages (if defined) or
     *   the version of the package from the require-dev packages (if defined).
     *   false otherwise
     * @throws UnexpectedValueException
     * @todo
     *   Consider adding a case when the package is defined in require and
     *   require-dev (currently returns version from require).
     */
    protected function getVersion($rootPackage)
    {
        $package = $this->config['package']['name'];
        $requires = [
            $rootPackage->getRequires(),
            $rootPackage->getDevRequires()
        ];

        foreach ($requires as $require) {
            if (isset($require[$package])) {
                $version = $require[$package]->getPrettyConstraint();
                return $this->validateVersion($version);
            }
        }

        return false;
    }

    /**
     * Validate that the version is an exact major.minor.patch version
     *
     * The url to download the code for the package only works with exact
     * version numbers with 3 digits: e.g. 1.2.3
     *
     * @access protected
     * @param string $version The version that should be validated
     * @return string The valid version
     * @throws UnexpectedValueException
     */
    protected function validateVersion($version)
    {
        // \A = start of string, \Z = end of string
        // See: http://stackoverflow.com/a/34994075
        $major_minor_patch = '/\A\d\.\d\.\d\Z/';

        if (!preg_match($major_minor_patch, $version)) {
            throw new \UnexpectedValueException(
                'The version constraint of advanced-custom-fields/' .
                'advanced-custom-fields-pro' .
                ' should be exact (with 3 digits). ' .
                'Invalid version string "' . $version . '"'
            );
        }

        return $version;
    }

    /**
     * Get the ACF PRO key from the environment
     *
     * Loads the .env file that is in the same directory as composer.json
     * and gets the key from the environment variable KEY_ENV_VARIABLE.
     * Already set variables will not be overwritten by the variables in .env
     * @link https://github.com/vlucas/phpdotenv#immutability
     *
     * @access protected
     * @return string The key from the environment
     * @throws PhilippBaschke\ACFProInstaller\Exceptions\MissingKeyException
     */
    protected function getKeyFromEnv()
    {
        $this->loadDotEnv();
        $key = getenv(self::KEY_ENV_VARIABLE);

        if (!$key) {
            throw new MissingKeyException(self::KEY_ENV_VARIABLE);
        }

        return $key;
    }

    /**
     * Make environment variables in .env available if .env exists
     *
     * getcwd() returns the directory of composer.json.
     *
     * @access protected
     */
    protected function loadDotEnv()
    {
        if (file_exists(getcwd().DIRECTORY_SEPARATOR.'.env')) {
            $dotenv = new Dotenv(getcwd());
            $dotenv->load();
        }
    }

    /**
     * Update the version and the key of the config
     *
     * The package version needs to match the required version and the dist url
     * needs to include the version and key as a parameter.
     *
     * @access protected
     * @param string The required version
     * @param string The ACF PRO license key
     */
    protected function updateConfig($version, $key)
    {
        $this->config['package']['version'] = $version;
        $this->addParameterToUrl('t', $version);
        $this->addParameterToUrl('k', $key);
    }

    /**
     * Add a parameter to the dist url
     *
     * Adds the given parameter at the end of the dist url. It only works with
     * urls that already have parameters (e.g. test.com?p=true) because it
     * uses & as a separation character.
     *
     * @access protected
     * @param string $parameter The name of the parameter
     * @param string $value The value of the parameter
     */
    protected function addParameterToUrl($parameter, $value)
    {
        $urlParameter = '&' . $parameter . '=' . urlencode($value);
        $this->config['package']['dist']['url'] .= $urlParameter;
    }
}
