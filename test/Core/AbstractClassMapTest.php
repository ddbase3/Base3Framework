<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTest extends TestCase {

	private string $tmpRoot;
	private string $basedir;

	protected function setUp(): void {
		if (!defined('DIR_TMP')) {
			$tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3-tests' . DIRECTORY_SEPARATOR;
			if (!is_dir($tmp)) @mkdir($tmp, 0777, true);
			define('DIR_TMP', $tmp);
		}

		$this->tmpRoot = rtrim(DIR_TMP, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'classmap_' . uniqid('', true) . DIRECTORY_SEPARATOR;
		$this->basedir = $this->tmpRoot . 'apps';
		@mkdir($this->basedir, 0777, true);
	}

	protected function tearDown(): void {
		$this->rmDir($this->tmpRoot);
	}

	private function rmDir(string $dir): void {
		if (!is_dir($dir)) return;
		$items = scandir($dir);
		if (!is_array($items)) return;
		foreach ($items as $it) {
			if ($it === '.' || $it === '..') continue;
			$path = $dir . DIRECTORY_SEPARATOR . $it;
			if (is_dir($path)) $this->rmDir($path);
			else @unlink($path);
		}
		@rmdir($dir);
	}

	private function makeContainer(array $services = []): IContainer {
		return new class($services) implements IContainer {

			private array $services;

			public function __construct(array $services) {
				$this->services = $services;
			}

			public function getServiceList(): array {
				return array_keys($this->services);
			}

			public function set(string $name, $classDefinition, $flags = 0): IContainer {
				$this->services[$name] = $classDefinition;
				return $this;
			}

			public function remove(string $name) {
				unset($this->services[$name]);
			}

			public function has(string $name): bool {
				return array_key_exists($name, $this->services);
			}

			public function get(string $name) {
				return $this->services[$name] ?? null;
			}
		};
	}

	private function writePhp(string $path, string $code): void {
		$dir = dirname($path);
		if (!is_dir($dir)) @mkdir($dir, 0777, true);
		file_put_contents($path, $code);
	}

	public function testGetMapTriggersGenerateWhenMissingAndLoadsMap(): void {
		$appDir = $this->basedir . DIRECTORY_SEPARATOR . 'MyApp';
		@mkdir($appDir, 0777, true);

		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'Foo.php', <<<'PHP'
<?php declare(strict_types=1);
namespace MyApp;

use Base3\Api\IBase;

class Foo implements IBase {
	public static function getName(): string { return 'foo'; }
}
PHP);

		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'Bar.php', <<<'PHP'
<?php declare(strict_types=1);
namespace MyApp;

interface IMarker {}
class Bar implements IMarker {}
PHP);

		$container = $this->makeContainer([]);
		$cm = new class($container, $this->basedir) extends AbstractClassMap {

			public int $generateCalls = 0;
			private string $basedir;

			public function __construct(IContainer $container, string $basedir) {
				parent::__construct($container);
				$this->basedir = $basedir;
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [
					['basedir' => $this->basedir],
				];
			}

			public function generate($regenerate = false): void {
				$this->generateCalls++;
				parent::generate($regenerate);
			}

			public function exposeGetMap(): array {
				$map = &$this->getMap();
				return $map;
			}
		};

		$map = $cm->exposeGetMap();

		$this->assertGreaterThan(0, $cm->generateCalls);
		$this->assertArrayHasKey('MyApp', $map);

		$this->assertArrayHasKey('interface', $map['MyApp']);
		$this->assertArrayHasKey('name', $map['MyApp']);

		$this->assertSame(\MyApp\Foo::class, $map['MyApp']['name']['foo']);
	}

	public function testGenerateSkipsWhenFileExistsAndNotRegenerate(): void {
		$container = $this->makeContainer([]);

		$cm = new class($container) extends AbstractClassMap {

			public int $scanCalls = 0;

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				$this->scanCalls++;
				return [];
			}

			public function writeDummy(): void {
				file_put_contents($this->classMapFile, "<?php return ['x' => 1];\n");
			}

			public function getFile(): string {
				return $this->classMapFile;
			}
		};

		$cm->writeDummy();
		$cm->generate(false);

		$this->assertSame(0, $cm->scanCalls);
		$this->assertFileExists($cm->getFile());
	}

	public function testGenerateFromComposerClassMapPathWhenMethodExists(): void {
		$container = $this->makeContainer([]);

		$cm = new class($container) extends AbstractClassMap {

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [];
			}

			protected function generateFromComposerClassMap(): void {
				$this->map = [
					'AppX' => [
						'name' => ['demo' => \Base3Test\Core\ClassMapDemo::class],
						'interface' => [],
					],
				];
			}

			public function exposeGetMap(): array {
				$map = &$this->getMap();
				return $map;
			}
		};

		if (!class_exists(\Base3Test\Core\ClassMapDemo::class, false)) {
			eval('namespace Base3Test\Core; use Base3\Api\IBase; class ClassMapDemo implements IBase { public static function getName(): string { return "demo"; } }');
		}

		$map = $cm->exposeGetMap();

		$this->assertArrayHasKey('AppX', $map);
		$this->assertSame(\Base3Test\Core\ClassMapDemo::class, $map['AppX']['name']['demo']);
	}

	public function testGetInstancesCriteriaAndRetryGenerate(): void {
		$appDir = $this->basedir . DIRECTORY_SEPARATOR . 'AppA';
		@mkdir($appDir, 0777, true);

		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'NamedThing.php', <<<'PHP'
<?php declare(strict_types=1);
namespace AppA;

use Base3\Api\IBase;

interface IA {}
class NamedThing implements IBase, IA {
	public static function getName(): string { return 'named'; }
}
PHP);

		$container = $this->makeContainer([]);

		$cm = new class($container, $this->basedir) extends AbstractClassMap {

			public int $generateCalls = 0;
			private string $basedir;

			public function __construct(IContainer $container, string $basedir) {
				parent::__construct($container);
				$this->basedir = $basedir;
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [['basedir' => $this->basedir]];
			}

			public function generate($regenerate = false): void {
				$this->generateCalls++;
				parent::generate($regenerate);
			}
		};

		// force map creation
		$cm->generate(true);

		$byName = $cm->getInstances(['name' => 'named']);
		$this->assertCount(1, $byName);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byName[0]);

		$byInterface = $cm->getInstances(['interface' => \AppA\IA::class]);
		$this->assertCount(1, $byInterface);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byInterface[0]);

		$byAppName = $cm->getInstances(['app' => 'AppA', 'name' => 'named']);
		$this->assertCount(1, $byAppName);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byAppName[0]);

		$byAppInterface = $cm->getInstances(['app' => 'AppA', 'interface' => \AppA\IA::class]);
		$this->assertCount(1, $byAppInterface);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byAppInterface[0]);

		$byAppInterfaceName = $cm->getInstances(['app' => 'AppA', 'interface' => \AppA\IA::class, 'name' => 'named']);
		$this->assertCount(1, $byAppInterfaceName);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byAppInterfaceName[0]);

		$byInterfaceName = $cm->getInstances(['interface' => \AppA\IA::class, 'name' => 'named']);
		$this->assertCount(1, $byInterfaceName);
		$this->assertInstanceOf(\AppA\NamedThing::class, $byInterfaceName[0]);

		// Retry path without autoload noise: use an existing interface that is not in the map.
		if (!interface_exists(\Base3Test\Core\IRetryMissing::class, false)) {
			eval('namespace Base3Test\Core; interface IRetryMissing {}');
		}

		$before = $cm->generateCalls;
		$none = $cm->getInstances(['app' => 'AppA', 'interface' => \Base3Test\Core\IRetryMissing::class]);
		$this->assertIsArray($none);
		$this->assertGreaterThanOrEqual($before + 1, $cm->generateCalls);
	}

	public function testInstantiateCoversConstructorResolution(): void {
		if (!class_exists(\Base3Test\Core\DepA::class, false)) {
			eval('namespace Base3Test\Core; class DepA {}');
		}
		if (!class_exists(\Base3Test\Core\DepB::class, false)) {
			eval('namespace Base3Test\Core; class DepB {}');
		}
		if (!interface_exists(\Base3Test\Core\IMockMe::class, false)) {
			eval('namespace Base3Test\Core; interface IMockMe { public function x(): int; }');
		}

		if (!class_exists(\Base3Test\Core\NoCtor::class, false)) {
			eval('namespace Base3Test\Core; class NoCtor { public function ping(): string { return "ok"; } }');
		}

		if (!class_exists(\Base3Test\Core\NeedsBuiltin::class, false)) {
			eval('namespace Base3Test\Core; class NeedsBuiltin { public function __construct(string $name) {} }');
		}

		if (!class_exists(\Base3Test\Core\NeedsClassType::class, false)) {
			eval('namespace Base3Test\Core; class NeedsClassType { public DepA $a; public function __construct(DepA $a) { $this->a = $a; } }');
		}

		if (!class_exists(\Base3Test\Core\NeedsByParamName::class, false)) {
			eval('namespace Base3Test\Core; class NeedsByParamName { public DepA $a; public function __construct(DepA $custom) { $this->a = $custom; } }');
		}

		if (!class_exists(\Base3Test\Core\UnionNeeds::class, false)) {
			eval('namespace Base3Test\Core; class UnionNeeds { public $v; public function __construct(DepA|DepB $dep) { $this->v = $dep; } }');
		}

		if (!class_exists(\Base3Test\Core\UnionDefault::class, false)) {
			eval('namespace Base3Test\Core; class UnionDefault { public $v; public function __construct(DepA|DepB $dep = null) { $this->v = $dep; } }');
		}

		if (!class_exists(\Base3Test\Core\NullableDefault::class, false)) {
			eval('namespace Base3Test\Core; class NullableDefault { public $v; public function __construct(?DepA $a = null) { $this->v = $a; } }');
		}

		if (!class_exists(\Base3Test\Core\NeedsMockedInterface::class, false)) {
			eval('namespace Base3Test\Core; class NeedsMockedInterface { public $m; public function __construct(IMockMe $m) { $this->m = $m; } }');
		}

		if (!class_exists(\Base3Test\Core\AbstractX::class, false)) {
			eval('namespace Base3Test\Core; abstract class AbstractX { abstract public function a(): int; }');
		}

		$depA = new \Base3Test\Core\DepA();

		$container = $this->makeContainer([
			\Base3Test\Core\DepA::class => $depA,
			// for builtin by param name
			'name' => 'n1',
			// for paramName class resolution (non-sensical name "custom" key)
			'custom' => $depA,
		]);

		$cm = new class($container) extends AbstractClassMap {
			protected function getScanTargets(): array { return []; }
		};

		$noCtor = $cm->instantiate(\Base3Test\Core\NoCtor::class);
		$this->assertInstanceOf(\Base3Test\Core\NoCtor::class, $noCtor);

		$needsBuiltin = $cm->instantiate(\Base3Test\Core\NeedsBuiltin::class);
		$this->assertInstanceOf(\Base3Test\Core\NeedsBuiltin::class, $needsBuiltin);

		$needsClass = $cm->instantiate(\Base3Test\Core\NeedsClassType::class);
		$this->assertInstanceOf(\Base3Test\Core\NeedsClassType::class, $needsClass);
		$this->assertSame($depA, $needsClass->a);

		$needsParamName = $cm->instantiate(\Base3Test\Core\NeedsByParamName::class);
		$this->assertInstanceOf(\Base3Test\Core\NeedsByParamName::class, $needsParamName);

		$union = $cm->instantiate(\Base3Test\Core\UnionNeeds::class);
		$this->assertInstanceOf(\Base3Test\Core\UnionNeeds::class, $union);
		$this->assertInstanceOf(\Base3Test\Core\DepA::class, $union->v);

		$unionDefault = $cm->instantiate(\Base3Test\Core\UnionDefault::class);
		$this->assertInstanceOf(\Base3Test\Core\UnionDefault::class, $unionDefault);

		$nullable = $cm->instantiate(\Base3Test\Core\NullableDefault::class);
		$this->assertInstanceOf(\Base3Test\Core\NullableDefault::class, $nullable);
		$this->assertNull($nullable->v);

		$needsMocked = $cm->instantiate(\Base3Test\Core\NeedsMockedInterface::class);
		$this->assertInstanceOf(\Base3Test\Core\NeedsMockedInterface::class, $needsMocked);
		$this->assertIsObject($needsMocked->m);

		// Abstract -> instantiate() should return null
		$this->assertNull($cm->instantiate(\Base3Test\Core\AbstractX::class));
	}

	public function testCheckDependenciesReportsWritability(): void {
		$container = $this->makeContainer([]);

		$cm = new class($container) extends AbstractClassMap {

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [];
			}

			public function exposeCheck(): array {
				return $this->checkDependencies();
			}
		};

		$res = $cm->exposeCheck();
		$this->assertArrayHasKey('classmap_writable', $res);
		$this->assertTrue(is_string($res['classmap_writable']));
	}

	public function testScanClassesSkipsNonPhpAndMultiDotAndAutoloaderSpecialCaseAndAbstract(): void {
		$appDir = $this->basedir . DIRECTORY_SEPARATOR . 'ScanApp';
		@mkdir($appDir, 0777, true);

		// should be ignored
		file_put_contents($appDir . DIRECTORY_SEPARATOR . 'readme.txt', 'x');
		file_put_contents($appDir . DIRECTORY_SEPARATOR . 'bad.name.php', '<?php');

		// should be ignored (special-case)
		$specialDir = $appDir . DIRECTORY_SEPARATOR . 'Base3Framework';
		@mkdir($specialDir, 0777, true);
		$this->writePhp($specialDir . DIRECTORY_SEPARATOR . 'Autoloader.php', <<<'PHP'
<?php declare(strict_types=1);
namespace ScanApp\Base3Framework;
class Autoloader {}
PHP);

		// abstract class ignored
		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'Abs.php', <<<'PHP'
<?php declare(strict_types=1);
namespace ScanApp;
abstract class Abs {}
PHP);

		// valid class
		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'Ok.php', <<<'PHP'
<?php declare(strict_types=1);
namespace ScanApp;
class Ok {}
PHP);

		$container = $this->makeContainer([]);

		$cm = new class($container, $this->basedir) extends AbstractClassMap {

			private string $basedir;

			public function __construct(IContainer $container, string $basedir) {
				parent::__construct($container);
				$this->basedir = $basedir;
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [['basedir' => $this->basedir, 'app' => 'ScanApp']];
			}

			public function scanOnly(): array {
				$classes = [];
				$this->scanClasses($classes, $this->basedir, 'ScanApp');
				return $classes;
			}
		};

		$classes = $cm->scanOnly();

		$found = array_map(fn($c) => $c['class'], $classes);

		$this->assertContains(\ScanApp\Ok::class, $found);
		$this->assertNotContains(\ScanApp\Abs::class, $found);
	}
}
