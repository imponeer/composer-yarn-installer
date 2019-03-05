<?php


namespace Imponeer\ComposerYarnInstaller;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;
use Imponeer\ComposerYarnInstaller\Exceptions\BadVersionFormatException;
use Imponeer\ComposerYarnInstaller\Exceptions\FileCantBeDownloadException;

/**
 * Helps to install yarn
 *
 * @package Imponeer\ComposerYarnInstaller
 */
class YarnInstaller
{
	/**
	 * Latest release candidate version
	 */
	const VERSION_RC = 'rc';

	/**
	 * Latest nighty build version
	 */
	const VERSION_NIGHTY_BUILD = 'nighty';

	/**
	 * Latest oficial version
	 */
	const VERSION_LATEST = 'latest';

	/**
	 * Input/output interface
	 *
	 * @var IOInterface
	 */
	private $io;

	/**
	 * YarnInstaller constructor.
	 *
	 * @param IOInterface $io
	 */
	public function __construct(IOInterface $io)
	{
		$this->io = $io;
	}

	/**
	 * Download yarn installation
	 *
	 * @param string $version Version to download
	 *
	 * @return string
	 *@throws FileCantBeDownloadException
	 *
	 * @throws BadVersionFormatException
	 */
	public function download(string $version = self::VERSION_LATEST): string
	{
		$url = $this->getDownloadURL($version);
		$tmpfname = tempnam(sys_get_temp_dir(), "yarn_installer");
		$downlader = new RemoteFilesystem($this->io);
		if (!$downlader->copy(parse_url($url, PHP_URL_HOST), $url, $tmpfname)) {
			throw new FileCantBeDownloadException();
		}
		$tmp_install_path = $this->getInstallPath() . 'tmp';
		$filesystem = new Filesystem();
		if (file_exists($tmp_install_path)) {
			$filesystem->removeDirectory($tmp_install_path);
		}
		$this->uncompress($tmpfname, $tmp_install_path);
		$install_path = $this->getInstallPath() . 'yarn';
		if (file_exists($install_path)) {
			$filesystem->removeDirectory($install_path);
		}
		$filesystem->copyThenRemove($this->getFirstDirectory($tmp_install_path), $install_path);
		$filesystem->removeDirectory($tmp_install_path);

		return $install_path;
	}

	/**
	 * Checks if is installed
	 *
	 * @return bool
	 */
	public static function isInstalled(): bool
	{
		$instance = new self();
		return file_exists(
			$instance->getInstallPath() . 'yarn'
		);
	}

	/**
	 * Gets download link
	 *
	 * @param string $version String
	 *
	 * @return string
	 *
	 * @throws BadVersionFormatException
	 */
	public function getDownloadURL(string $version = self::VERSION_LATEST): string
	{
		switch ($version) {
			case self::VERSION_LATEST:
				return 'https://yarnpkg.com/latest.tar.gz';
				break;
			case self::VERSION_NIGHTY_BUILD:
				return 'https://nightly.yarnpkg.com/latest.tar.gz';
				break;
			case self::VERSION_RC:
				return 'https://yarnpkg.com/latest-rc.tar.gz';
				break;
			default:
				if (!preg_match('^[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+$', $version)) {
					throw new BadVersionFormatException();
				}
				return "https://yarnpkg.com/downloads/$version/yarn-v$version.tar.gz";
		}
	}

	/**
	 * Get installation path
	 *
	 * @return string
	 */
	public function getInstallPath(): string
	{
		return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'opt' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Uncompress archive
	 *
	 * @param string $src Archive path
	 * @param string $dst Uncompress path
	 *
	 * @return bool
	 */
	protected function uncompress(string $src, string $dst): bool
	{
		$phar = new \PharData($src);
		return $phar->extractTo($dst);
	}

	/**
	 * Gets first directory in path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function getFirstDirectory(string $path): string
	{
		foreach (new \DirectoryIterator($path) as $fileinfo) {
			if ($fileinfo->isDot()) {
				continue;
			}
			return $fileinfo->getPathname();
		}
	}

}