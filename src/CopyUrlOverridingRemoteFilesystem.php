<?php namespace Pivvenit\Composer\Installers\ACFPro;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;

/**
 * A composer remote filesystem that enables to use a different URL for copy
 */
class CopyUrlOverridingRemoteFilesystem extends RemoteFilesystem
{
    /**
     * The file url that should be used instead of the given file url in copy
     *
     * @access protected
     * @var string
     */
    protected $realUrl;

    /**
     * Constructor
     *
     * @access public
     * @param string $realUrl The url that should be used instead of fileurl
     * @param IOInterface $io The IO instance
     * @param Config $config The config
     * @param array $options The options
     * @param bool $disableTls
     */
    public function __construct(
        $realUrl,
        IOInterface $io,
        Config $config = null,
        array $options = [],
        $disableTls = false
    )
    {
        $this->realUrl = $realUrl;
        parent::__construct($io, $config, $options, $disableTls);
    }

    /**
     * Copy the remote file in local
     *
     * Uses the specified URL instead of the provided URL
     *
     * @param string $originUrl The origin URL
     * @param string $fileUrl The file URL (ignored)
     * @param string $fileName the local filename
     * @param bool $progress Display the progression
     * @param array $options Additional context options
     *
     * @return bool true
     */
    public function copy(
        $originUrl,
        $fileUrl,
        $fileName,
        $progress = true,
        $options = []
    )
    {
        return parent::copy(
            $originUrl,
            $this->realUrl,
            $fileName,
            $progress,
            $options
        );
    }
}
