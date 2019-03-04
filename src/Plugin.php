<?php

namespace Imponeer\ComposerYarnInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use MatthiasMullie\PathConverter\Converter;
use Mouf\NodeJsInstaller\Environment;
use Mouf\NodeJsInstaller\NodeJsInstaller;
use Symfony\Component\Process\Process;

/**
 * Defines plugin functionality
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

	/**
	 * Gets all events that can be subscribed with this plugin
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			ScriptEvents::POST_INSTALL_CMD => 'onNodeDownload',
			ScriptEvents::POST_UPDATE_CMD => 'onNodeDownload'
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

	}

	/**
	 * Installs yarn after node downloaded and installed
	 *
	 * @param Event $event
	 */
	public function onNodeDownload(Event $event)
	{
		$composer = $event->getComposer();
		$settings = $this->getSettings($composer);
		$io = $event->getIO();

		$binDir = $composer->getConfig()->get('bin-dir');

		if (file_exists($settings['targetDir']) || $settings['forceLocal']) {
			$path = $settings['targetDir'];
			$is_local = true;
		} else {
			$nodeJsInstaller = new NodeJsInstaller($io);
			$path = $nodeJsInstaller->getGlobalInstallPath('npm');
			$is_local = false;
		}
		$this->verboseWrite('Found node path:' . $path, $io);

		if ($is_local) {
			$this->verboseWrite('Executing: npm install yarn', $io);
			if (Environment::isWindows()) {
				$this->exec('.\\npm install yarn --no-save --no-package-lock', $path, $io);
			} else {
				$this->exec('./npm install yarn --no-save --no-package-lock', $path, $io);
			}
		} else {
			$this->verboseWrite('Executing: npm install yarn', $io);
			$this->exec('npm install -g yarn --no-save --no-package-lock', $path, $io);
		}

		$this->createBinScripts($binDir, $path, $is_local);
	}

	/**
	 * Gets settings
	 *
	 * @param Composer $composer Current composer instance
	 *
	 * @return array
	 */
	protected function getSettings(Composer $composer)
	{
		$settings = array(
			'targetDir' => 'vendor/nodejs/nodejs',
			'forceLocal' => false,
			'includeBinInPath' => false,
		);

		$extra = $composer->getPackage()->getExtra();

		if (isset($extra['mouf']['nodejs'])) {
			$rootSettings = $extra['mouf']['nodejs'];
			$settings = array_merge($settings, $rootSettings);
			$settings['targetDir'] = trim($settings['targetDir'], '/\\');
		}

		return $settings;
	}

	/**
	 * Writes message when verbose mode is enabled.
	 *
	 * @param string $message Message to write
	 * @param IOInterface $io InputOutput interface
	 */
	private function verboseWrite($message, IOInterface $io)
	{
		if ($io->isVerbose()) {
			$io->write($message);
		}
	}

	/**
	 * Execute command
	 *
	 * @param string $cmd Command to execute
	 * @param string $path Path where to run
	 * @param IOInterface $io InputOutput interface
	 */
	protected function exec($cmd, $path, IOInterface $io)
	{
		$process = new Process($cmd, $path);
		$process->mustRun(function ($type, $buffer) use ($io) {
			if ($io->isVerbose()) {
				if (Process::ERR === $type) {
					$io->writeError($buffer);
				} else {
					$io->write($buffer);
				}
			}
		});
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
		if (!Environment::isWindows()) {
			$this->writeShLinkScript($binDir, $fullTargetDir, 'yarn', 'node_modules/yarn/bin/yarn', $isLocal);
			$this->writeShLinkScript($binDir, $fullTargetDir, 'yarnpkg', 'node_modules/yarn/bin/yarnpkg', $isLocal);
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
		$converter = new Converter(
			realpath($fullTargetDir),
			realpath($binPath)
		);
		$ret = $converter->convert($filename);
		if (Environment::isWindows()) {
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
