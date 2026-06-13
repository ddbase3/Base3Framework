<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IRequest;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Configuration\Api\IConfiguration;
use Base3\Hook\IHookManager;
use Base3\ServiceSelector\Api\IServiceSelector;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase {

	private ?string $tmpDir = null;

	protected function tearDown(): void {
		$this->resetServiceLocatorSingletons();

		if ($this->tmpDir !== null && is_dir($this->tmpDir)) {
			$this->rmDirRecursive($this->tmpDir);
		}

		putenv('CONFIG_FILE');
	}

	private function resetServiceLocatorSingletons(): void {
		$ref = new \ReflectionClass(ServiceLocator::class);

		foreach (['instance', 'externalInstance'] as $propName) {
			$prop = $ref->getProperty($propName);
			$prop->setAccessible(true);
			$prop->setValue(null, null);
		}
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
		$dir = $base . DIRECTORY_SEPARATOR . 'base3-bootstrap-' . bin2hex(random_bytes(8));
		if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
			self::fail('Could not create temp directory: ' . $dir);
		}
		$this->tmpDir = $dir;
		return $dir;
	}

	private function defineConstIfMissing(string $fqcn, string $value): void {
		if (!defined($fqcn)) {
			define($fqcn, $value);
		}
	}

	private function getUsedDirTmp(): ?string {
		$ns = __NAMESPACE__ . '\\DIR_TMP';
		if (defined($ns)) return constant($ns);
		if (defined('DIR_TMP')) return DIR_TMP;
		return null;
	}

	private function ensureMinimalConfigViaEnv(string $baseDir): void {
		$cnfDir = $baseDir . DIRECTORY_SEPARATOR . 'cnf';
		@mkdir($cnfDir, 0777, true);

		$configIni = $cnfDir . DIRECTORY_SEPARATOR . 'config.ini';
		file_put_contents(
			$configIni,
			"[base]\nurl = \"http://localhost/\"\nintern = \"\"\n\n[directories]\ndata = \"\"\n"
		);

		putenv('CONFIG_FILE=' . $configIni);

		// also avoid potential namespaced DIR_CNF fatal in ConfigFile constructor if it ever gets used elsewhere
		$this->defineConstIfMissing('Base3\\Configuration\\ConfigFile\\DIR_CNF', rtrim($cnfDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
	}

	private function writeEmptyClassMapToUsedTmp(string $baseDir): void {
		$tmpDir = $this->getUsedDirTmp();

		if ($tmpDir === null) {
			$tmpDir = $baseDir . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
			@mkdir($tmpDir, 0777, true);

			$this->defineConstIfMissing(__NAMESPACE__ . '\\DIR_TMP', $tmpDir);
			$this->defineConstIfMissing('DIR_TMP', $tmpDir);
		}

		$tmpDir = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		@mkdir($tmpDir, 0777, true);

		file_put_contents($tmpDir . 'classmap.php', "<?php return [];\n");
	}

	private function ensureFileTokenDirLocalConstant(string $baseDir): void {
		$dir = $baseDir . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR;
		@mkdir($dir, 0777, true);

		$this->defineConstIfMissing('Base3\\Token\\FileToken\\DIR_LOCAL', $dir);
	}

	public function testRunRegistersContainerServicesAndEchoesResponse(): void {
		$this->resetServiceLocatorSingletons();

		$baseDir = $this->makeTmpDir();

		$this->ensureFileTokenDirLocalConstant($baseDir);
		$this->ensureMinimalConfigViaEnv($baseDir);
		$this->writeEmptyClassMapToUsedTmp($baseDir);

		$bootstrap = new Bootstrap();

		$obLevelBefore = ob_get_level();
		ob_start();
		try {
			$bootstrap->run();
			$output = (string)ob_get_contents();
		} finally {
			while (ob_get_level() > $obLevelBefore) {
				ob_end_clean();
			}
		}

		$container = ServiceLocator::getInstance();

		self::assertInstanceOf(ServiceLocator::class, $container);

		self::assertTrue($container->has('servicelocator'));
		self::assertTrue($container->has(IRequest::class));
		self::assertTrue($container->has(IContainer::class));
		self::assertTrue($container->has(IHookManager::class));
		self::assertTrue($container->has('configuration'));
		self::assertTrue($container->has(IConfiguration::class));
		self::assertTrue($container->has('classmap'));
		self::assertTrue($container->has(IClassMap::class));
		self::assertTrue($container->has('accesscontrol'));
		self::assertTrue($container->has(IAccesscontrol::class));
		self::assertTrue($container->has(IServiceSelector::class));
		self::assertTrue($container->has('middlewares'));

		self::assertSame($container->get('servicelocator'), $container->get(IContainer::class));
		self::assertSame($container->get('configuration'), $container->get(IConfiguration::class));
		self::assertSame($container->get('classmap'), $container->get(IClassMap::class));
		self::assertSame($container->get('accesscontrol'), $container->get(IAccesscontrol::class));

		self::assertSame($container->get(IHookManager::class), $container->get(IHookManager::class));
		self::assertSame($container->get(IRequest::class), $container->get(IRequest::class));
		self::assertSame($container->get(IClassMap::class), $container->get(IClassMap::class));
		self::assertSame($container->get(IAccesscontrol::class), $container->get(IAccesscontrol::class));
		self::assertSame($container->get(IServiceSelector::class), $container->get(IServiceSelector::class));

		self::assertSame([], $container->get('middlewares'));

		// Output depends on environment/plugins/routes. Ensure it's a string and one of the expected stable outcomes.
		self::assertIsString($output);
		self::assertContains($output, ['', "404 Not Found\n"]);
	}
}
