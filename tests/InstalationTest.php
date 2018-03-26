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
	 * Directory where test data is located
	 *
	 * @var string[]
	 */
	protected $dirs;

	/**
	 * Test composer install command (local)
	 */
	public function testLocalInstall()
	{
		$this->mutual_test_code($this->dirs[0], 'install');
	}

	/**
	 * Tests that shared between update and install
	 *
	 * @param string $dir Directory where to run
	 * @param string $command Command to execute
	 */
	private function mutual_test_code($dir, $command)
	{
		$input = new ArrayInput(
			array(
				'command' => $command,
				'working-dir' => $dir
			)
		);
		fwrite(STDOUT, 'Executing composer command: ' . $command);
		$application = new Application();
		$application->setAutoExit(false);
		$exit_code = $application->run($input);
		$bin_dir = $application->getComposer()->getConfig()->get('bind-dir');
		fwrite(STDOUT, 'Bin dir: ' . $bin_dir);

		$this->assertEquals(0, $exit_code, 'Composer installer exited with non-zero status');
		$exec = Environment::isWindows() ? array('yarn.bat', 'yarnpkg.bat') : array('yarn', 'yarnpkg');
		foreach ($exec as $file) {
			$full_path = realpath($bin_dir) . DIRECTORY_SEPARATOR . $file;
			$this->assertFileExists($full_path, $file . ' doesn\'t exist in bin');
			$this->assertTrue(is_executable($full_path), $file . ' is not executable');
			fwrite(STDOUT, 'Executing command: ' . $full_path . ' --help');
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
	}

	/**
	 * Test composer install command (local)
	 */
	public function testLocalUpdate()
	{
		$this->mutual_test_code($this->dirs[0], 'update');
	}

	/**
	 * Test composer install command (global)
	 */
	public function testGlobalInstall()
	{
		$this->mutual_test_code($this->dirs[1], 'install');
	}

	/**
	 * Test composer update command (global)
	 */
	public function testGlobalUpdate()
	{
		$this->mutual_test_code($this->dirs[1], 'update');
	}

	/**
	 * Setups tests
	 */
	protected function setUp()
	{
		@ini_set('memory_limit', '512M');
		$this->dirs[0] = $this->generateRandomFolder();
		$this->dirs[1] = $this->generateRandomFolder();
		$filesystem = new Filesystem();
		$filesystem->mkdir($this->dirs[0], 0777);
		$filesystem->mkdir($this->dirs[1], 0777);
		$filesystem->dumpFile(
			$this->dirs[0] . DIRECTORY_SEPARATOR . 'composer.json',
			$this->generate_composer_json(true)
		);
		$filesystem->dumpFile(
			$this->dirs[1] . DIRECTORY_SEPARATOR . 'composer.json',
			$this->generate_composer_json(false)
		);
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
	 * Cleanups after tests
	 */
	protected function tearDown()
	{
		$filesystem = new FS();
		array_walk($this->dirs, array($filesystem, 'removeDirectory'));
	}

}
