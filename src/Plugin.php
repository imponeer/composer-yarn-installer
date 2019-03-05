<?php

namespace Imponeer\ComposerYarnInstaller;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\Platform;

/**
 * Defines plugin functionality
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
	/**
	 * Current composer instance
	 *
	 * @var Composer
	 */
	protected $composer;

	/**
	 * Current io
	 *
	 * @var IOInterface
	 */
	protected $io;

	/**
	 * Gets all events that can be subscribed with this plugin
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			ScriptEvents::POST_INSTALL_CMD => 'onPreInstall',
			ScriptEvents::POST_UPDATE_CMD => 'onPreUpdate',
			Events::ON_YARN_INSTALL => 'onYarnDownload'
		);
	}

	/**
	 * Activate plugin
	 *
	 * @param Composer $composer Current composer instance
	 * @param IOInterface $io Current Input-Output interface
	 */
	public function activate(Composer $composer, IOInterface $io)
	{
		$this->composer = $composer;
		$this->io = $io;
	}

	/**
	 * Event that triggers before install
	 *
	 * @param Event $event
	 */
	public function onPreInstall(Event $event)
	{
		$this->composer->getEventDispatcher()->dispatch(Events::ON_YARN_INSTALL);
	}

	/**
	 * Event that triggers before update
	 *
	 * @param Event $event
	 */
	public function onPreUpdate(Event $event)
	{
		$this->composer->getEventDispatcher()->dispatch(Events::ON_YARN_INSTALL);
	}

	/**
	 * Installs yarn
	 *
	 * @param Event $event
	 */
	public function onYarnDownload(Event $event)
	{
		$extra = $this->composer->getPackage()->getExtra();
		$binDir = $this->composer->getConfig()->get('bin-dir');
		$version = isset($extra['yarn_version']) ? $extra['yarn_version'] : YarnInstaller::VERSION_LATEST;

		$installer = new YarnInstaller($this->io);
		$path = $installer->download($version);

		$this->verboseWrite("Creating bin links...");
		$this->createBinScripts($binDir, $path . DIRECTORY_SEPARATOR . 'bin', true);
	}

	/**
	 * Writes message when verbose mode is enabled.
	 *
	 * @param string $message Message to write
	 */
	private function verboseWrite($message)
	{
		if ($this->io->isVerbose()) {
			$this->io->write($message);
		}
	}

	/**
	 * Create bin scripts
	 *
	 * @param string $binDir vendor/bin dir
	 * @param string $fullTargetDir NodeJS instalation path
	 * @param bool $isLocal Is local
	 */
	protected function createBinScripts($binDir, $fullTargetDir, $isLocal)
	{
		if (!file_exists($binDir)) {
			mkdir($binDir);
		}
		if (!Platform::isWindows()) {
			$this->writeShLinkScript($binDir, $fullTargetDir, 'yarn', 'yarn', $isLocal);
			$this->writeShLinkScript($binDir, $fullTargetDir, 'yarnpkg', 'yarnpkg', $isLocal);
			chmod($binDir . '/yarn', 0755);
			chmod($binDir . '/yarnpkg', 0755);
		} else {
			$this->writeBatLinkScript($binDir, $fullTargetDir, 'yarn.bat', 'yarn.cmd', $isLocal);
			$this->writeBatLinkScript($binDir, $fullTargetDir, 'yarnpkg.bat', 'yarnpkg.cmd', $isLocal);
		}
	}

	/**
	 * Write .sh script
	 *
	 * @param string $binPath vendor/bin path
	 * @param string $fullTargetDir Where script that must be executed is locate
	 * @param string $script_filename Script filename where to write contents
	 * @param string $exec_filename Filename to execute
	 * @param bool $isLocal Is local?
	 *
	 * @return bool
	 */
	protected function writeShLinkScript($binPath, $fullTargetDir, $script_filename, $exec_filename, $isLocal)
	{
		return $this->writeBinScript(
			$binPath,
			$script_filename,
			array(
				'#!/usr/bin/env bash',
				'DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"',
				'$DIR/' . $this->getFilePathForScript(
					$binPath,
					$fullTargetDir,
					$exec_filename,
					$isLocal
				) . ' $@'
			)
		);
	}

	/**
	 * Writes bin script
	 *
	 * @param $path
	 * @param $filename
	 * @param $commands
	 */
	protected function writeBinScript($path, $filename, array $commands)
	{
		return file_put_contents($path . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $commands), LOCK_EX) !== false;
	}

	/**
	 * Gets filepath for script
	 *
	 * @param string $binPath Bin path
	 * @param string $fullTargetDir Traget dir
	 * @param string $filename Filename
	 * @param bool $isLocal Is local?
	 *
	 * @return string
	 */
	protected function getFilePathForScript($binPath, $fullTargetDir, $filename, $isLocal)
	{
		if (!$isLocal) {
			return $fullTargetDir . DIRECTORY_SEPARATOR . $filename;
		}
		$fs = new Filesystem();
		$ret = $fs->findShortestPath($binPath, $fullTargetDir, true) . DIRECTORY_SEPARATOR . basename($filename);
		if (Platform::isWindows()) {
			$ret = str_replace('/', '\\', $ret);
		}
		return $ret;
	}

	/**
	 * Write .bat script
	 *
	 * @param string $binPath vendor/bin path
	 * @param string $fullTargetDir Where script that must be executed is locate
	 * @param string $script_filename Script filename where to write contents
	 * @param string $exec_filename Filename to execute
	 * @param bool $isLocal Is local?
	 *
	 * @return bool
	 */
	protected function writeBatLinkScript($binPath, $fullTargetDir, $script_filename, $exec_filename, $isLocal)
	{
		return $this->writeBinScript(
			$binPath,
			$script_filename,
			array(
				'@ECHO OFF',
				($isLocal ? '%~dp0' : '') .
				$this->getFilePathForScript(
					$binPath,
					$fullTargetDir,
					$exec_filename,
					$isLocal
				) . ' %*'
			)
		);
	}


}
