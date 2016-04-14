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
     */
    const REPOSITORY_FILE = __DIR__ . '/repository.json';

    public function activate(Composer $composer, IOInterface $io)
    {
        $config = json_decode(file_get_contents(self::REPOSITORY_FILE), true);
        $repository = $composer->getRepositoryManager()
                    ->createRepository($config['type'], $config);
        $composer->getRepositoryManager()->prependRepository($repository);
    }
}
