<?php

namespace Imponeer\ComposerYarnInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
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
		$io = $event->getIO();
		if ($io->isVerbose()) {
			$io->write('Installing yarn...');
		}
		$binDir = $event->getComposer()->getConfig()->get('bin-dir');

		$cwd = getcwd();
		chdir($binDir);
		$process = new Process("npm install yarn");
		$process->run(function ($type, $buffer) use ($io) {
			if (Process::ERR === $type) {
				$io->writeError($buffer);
			} else {
				$io->write($buffer);
			}
		});
		chdir($cwd);
	}
}