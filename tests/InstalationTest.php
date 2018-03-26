<?php

namespace Imponeer\ComposerYarnInstaller;

use Composer\Console\Application;
use Composer\Util\Filesystem as FS;
use Mouf\NodeJsInstaller\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Tests if instalation is ok
 *
 * @runTestsInSeparateProcesses
 */
class InstalationTest extends TestCase
{

	/**
	 * Test composer install command (local)
	 */
	public function testLocalInstall()
	{
		$this->mutual_test_code($this->generateRandomFolder(), 'install', true);
	}

	/**
	 * Tests that shared between update and install
	 *
	 * @param string $dir Directory where to run
	 * @param string $command Command to execute
	 * @param bool $is_local Is local yarn install?
	 */
	private function mutual_test_code($dir, $command, $is_local)
	{
		$filesystem = new Filesystem();
		$filesystem->mkdir($dir, 0777);
		$filesystem->dumpFile(
			$dir . DIRECTORY_SEPARATOR . 'composer.json',
			$this->generate_composer_json($is_local)
		);

		$input = new ArrayInput(
			array(
				'command' => $command,
				'--working-dir' => $dir,
				'--no-interaction' => true,
				'--no-dev' => true,
				'--no-suggest' => true,
				'--optimize-autoloader' => true,
				'-v' => true
			)
		);
		fwrite(STDERR, 'Executing composer command: ' . $command);

		$application = new Application();
		$application->setAutoExit(false);
		$exit_code = $application->doRun($input);
		$bin_dir = $application->getComposer()->getConfig()->get('bind-dir');
		fwrite(STDERR, 'Bin dir: ' . $bin_dir);

		$this->assertEquals(0, $exit_code, 'Composer installer exited with non-zero status');
		$exec = Environment::isWindows() ? array('yarn.bat', 'yarnpkg.bat') : array('yarn', 'yarnpkg');
		foreach ($exec as $file) {
			$full_path = realpath($bin_dir) . DIRECTORY_SEPARATOR . $file;
			$this->assertFileExists($full_path, $file . ' doesn\'t exist in bin');
			$this->assertTrue(is_executable($full_path), $file . ' is not executable');
			fwrite(STDERR, 'Executing command: ' . $full_path . ' --help');
			$process = new Process(
				array(
					$file,
					'--help'
				),
				realpath($bin_dir)
			);
			try {
				$process->mustRun();
				$execution_ok = true;
			} catch (\Exception $ex) {
				$execution_ok = false;
			}
			$this->assertTrue($execution_ok, $file . ' failed to test if --help command works');
		}

		$fs = new FS();
		$fs->removeDirectory($dir);
	}

	private function generate_composer_json($is_local)
	{
		return json_encode([
			'name' => 'test-' . sha1(microtime(true)) . '-' . (string)((int)$is_local),
			'description' => 'Just a dummy composer plugin for testing',
			'license' => 'PDDL-1.0',
			'type' => 'project',
			'authors' => [
				[
					'name' => 'Some bot',
					'email' => 'Get.it@i.am.not.real'
				]
			],
			"repositories" => [
				[
					"type" => "path",
					"url" => dirname(__DIR__),
					"options" => [
						"symlink" => false
					]
				]
			],
			'require' => [
				'imponeer/composer-yarn-installer' => '*'
			],
			'minimum-stability' => 'dev',
			'prefer-stable' => false,
			"extra" => [
				"mouf" => [
					"nodejs" => [
						"forceLocal" => $is_local
					]
				]
			]
		]);
	}

	/**
	 * Generates random folder
	 *
	 * @return string
	 */
	public function generateRandomFolder()
	{
		$tmp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
		while (
		file_exists(
			$dir = $tmp_dir . 'test_' . sha1(microtime(true))
		)
		) ;
		return $dir;
	}

	/**
	 * Test composer install command (global)
	 */
	public function testGlobalInstall()
	{
		$this->mutual_test_code($this->generateRandomFolder(), 'install', true);
	}

	/**
	 * Test composer update command (global)
	 */
	public function testGlobalUpdate()
	{
		$this->mutual_test_code($this->generateRandomFolder(), 'update', false);
	}

	/**
	 * Test composer install command (local)
	 */
	public function testLocalUpdate()
	{
		$this->mutual_test_code($this->generateRandomFolder(), 'update', false);
	}

	/**
	 * Setups tests
	 */
	protected function setUp()
	{
		@ini_set('memory_limit', '512M');
		@set_time_limit(0);
	}

}
