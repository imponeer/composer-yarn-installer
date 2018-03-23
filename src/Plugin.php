<?php

namespace Imponeer\ComposerYarnInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Mouf\NodeJsInstaller\NodeJsInstaller;
use Mouf\NodeJsInstaller\NodeJsVersionMatcher;
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

		if (file_exists($settings['targetDir'])) {
			$path = $settings['targetDir'];
		} else {
			$nodeJsInstaller = new NodeJsInstaller($io);
			$path = $nodeJsInstaller->getGlobalInstallPath('npm');
		}

		$this->exec('npm update', $path, $io);
		$this->exec('npm install yarn', $path, $io);
	}

	/**
	 * Execute command
	 *
	 * @param string $cmd		Command to execute
	 * @param string $path		Path where to run
	 * @param IOInterface $io	InputOutput interface
	 */
	protected function exec($cmd, $path, IOInterface $io) {
		$process = new Process($cmd, $path);
		$process->mustRun(function ($type, $buffer) use ($io) {
			if (Process::ERR === $type) {
				$io->writeError($buffer);
			} else {
				$io->write($buffer);
			}
		});
	}

	/**
	 * Gets settings
	 *
	 * @param Composer $composer  Current composer instance
	 *
	 * @return array
	 */
	protected function getSettings(Composer $composer) {
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

}
