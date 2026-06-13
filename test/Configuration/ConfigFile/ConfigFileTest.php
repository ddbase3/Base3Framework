<?php declare(strict_types=1);

namespace Base3\Configuration\ConfigFile;

use PHPUnit\Framework\TestCase;

final class ConfigFileTest extends TestCase {

	private ?string $tmpDir = null;

	protected function tearDown(): void {
		if ($this->tmpDir !== null && is_dir($this->tmpDir)) {
			$this->rmDirRecursive($this->tmpDir);
		}

		// prevent bleed into other tests
		putenv('CONFIG_FILE');
	}

	private function rmDirRecursive(string $dir): void {
		$items = @scandir($dir);
		if ($items === false) return;

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') continue;
			$path = $dir . DIRECTORY_SEPARATOR . $item;

			if (is_dir($path)) {
				$this->rmDirRecursive($path);
				@rmdir($path);
				continue;
			}

			@unlink($path);
		}

		@rmdir($dir);
	}

	private function makeTmpDir(): string {
		$base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
		$dir = $base . DIRECTORY_SEPARATOR . 'base3-configfile-' . bin2hex(random_bytes(8));
		if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
			self::fail('Could not create temp directory: ' . $dir);
		}
		$this->tmpDir = $dir;
		return $this->tmpDir;
	}

	private function ensureDirCnfConstant(string $cnfDir): void {
		$cnfDir = rtrim($cnfDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		// ConfigFile resolves DIR_CNF inside its namespace first:
		// Base3\Configuration\ConfigFile\DIR_CNF
		$namespaced = __NAMESPACE__ . '\\DIR_CNF';
		if (!defined($namespaced)) {
			define($namespaced, $cnfDir);
		}

		// also define global to avoid surprises elsewhere
		if (!defined('DIR_CNF')) {
			define('DIR_CNF', $cnfDir);
		}
	}

	public function testConstructorUsesEnvConfigFileIfSet(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'my.ini';
		file_put_contents($file, "[base]\nurl=\"x\"\nintern=\"\"\n");

		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();

		self::assertSame('x', $cfg->get('base')['url']);
	}

	public function testGetReturnsEmptyArrayIfFileDoesNotExist(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'missing.ini';
		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();

		self::assertSame([], $cfg->get());
		self::assertNull($cfg->get('base'));
	}

	public function testGetReturnsSectionOrNull(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'config.ini';
		file_put_contents($file, "[base]\nurl=\"http://localhost/\"\nintern=\"\"\n");

		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();

		self::assertSame('http://localhost/', $cfg->get('base')['url']);
		self::assertNull($cfg->get('does_not_exist'));
	}

	public function testSetWithSectionSetsOnlyThatSection(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'config.ini';
		file_put_contents($file, "[base]\nurl=\"u\"\nintern=\"\"\n");

		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();
		$cfg->set(['data' => '/tmp'], 'directories');

		self::assertSame(['data' => '/tmp'], $cfg->get('directories'));
		self::assertSame('u', $cfg->get('base')['url']);
	}

	public function testSetWithoutSectionReplacesRootConfig(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'config.ini';
		file_put_contents($file, "[base]\nurl=\"u\"\nintern=\"\"\n");

		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();
		$cfg->set([
			'base' => ['url' => 'x', 'intern' => ''],
			'directories' => ['data' => 'd'],
		]);

		self::assertSame('x', $cfg->get('base')['url']);
		self::assertSame('d', $cfg->get('directories')['data']);
	}

	public function testSaveWritesIniFile(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'config.ini';
		file_put_contents($file, "[base]\nurl=\"u\"\nintern=\"\"\n");

		putenv('CONFIG_FILE=' . $file);

		$cfg = new ConfigFile();
		$cfg->set([
			'base' => ['url' => 'http://example.test/', 'intern' => ''],
			'directories' => ['data' => 'DATA'],
		]);

		// save() is BC-void now (it calls trySave()), so assert via trySave()
		self::assertTrue($cfg->trySave());
		self::assertFileExists($file);
		self::assertGreaterThan(0, filesize($file));

		$reloaded = parse_ini_file($file, true);

		self::assertSame('http://example.test/', $reloaded['base']['url']);
		self::assertSame('DATA', $reloaded['directories']['data']);
	}

	public function testCheckDependenciesReportsConfigAndDataDirectory(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$file = $dir . DIRECTORY_SEPARATOR . 'config.ini';

		// Missing file -> config_file_exists should not be Ok, data dir not defined
		putenv('CONFIG_FILE=' . $file);
		$cfgMissing = new ConfigFile();

		$depsMissing = $cfgMissing->checkDependencies();
		self::assertArrayHasKey('config_file_exists', $depsMissing);
		self::assertArrayHasKey('data_directory_defined', $depsMissing);
		self::assertNotSame('Ok', $depsMissing['config_file_exists']);
		self::assertNotSame('Ok', $depsMissing['data_directory_defined']);

		// Present file with directories[data] -> both Ok
		file_put_contents($file, "[directories]\ndata=\"/tmp\"\n");
		$cfg = new ConfigFile();

		$deps = $cfg->checkDependencies();
		self::assertSame('Ok', $deps['config_file_exists']);
		self::assertSame('Ok', $deps['data_directory_defined']);
	}

	public function testIncludeFilesAreLoadedFromDataDirectoryAndIncludeSectionIsRemoved(): void {
		$dir = $this->makeTmpDir();
		$this->ensureDirCnfConstant($dir);

		$main = $dir . DIRECTORY_SEPARATOR . 'config.ini';
		$dataDir = $dir . DIRECTORY_SEPARATOR . 'data';
		@mkdir($dataDir, 0777, true);

		file_put_contents($dataDir . DIRECTORY_SEPARATOR . 'extra.ini', "[extra]\nfoo=\"bar\"\n");

		$ini = <<<INI
[directories]
data = "$dataDir"

[include]
files[] = "extra.ini"

[base]
url = "u"
intern = ""
INI;

		file_put_contents($main, $ini);

		putenv('CONFIG_FILE=' . $main);

		$cfg = new ConfigFile();
		$all = $cfg->get();

		self::assertSame('bar', $cfg->get('extra')['foo']);
		self::assertSame('u', $cfg->get('base')['url']);

		self::assertArrayNotHasKey('include', $all);
	}
}
