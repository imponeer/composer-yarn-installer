<?php

namespace Imponeer\ComposerYarnInstaller;

use Mouf\NodeJsInstaller\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests if instalation is ok
 *
 * @runTestsInSeparateProcesses
 */
class InstalationTest extends TestCase
{
	/**
	 * Setups tests
	 */
	protected function setUp()
	{
		@ini_set('memory_limit', '512M');
		@set_time_limit(0);
	}

	/**
	 * Tests instalation
	 */
	private function testInstalation()
	{
		$bin_dir = 'vendor' . DIRECTORY_SEPARATOR . 'bin';
		$exec = Environment::isWindows() ? array('yarn.bat', 'yarnpkg.bat') : array('yarn', 'yarnpkg');
		foreach ($exec as $file) {
			$full_path = realpath($bin_dir . DIRECTORY_SEPARATOR . $file);
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
	}

}
