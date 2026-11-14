<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Core;

use Base3\CoreFoundation\Api\IBase;
use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Base\AbstractClassMap;
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
		return new AbstractClassMapTestDouble1($services);
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

use Base3\CoreFoundation\Api\IBase;

class Foo implements IBase {
	public static function getName(): string { return 'foo'; }
}
PHP);

		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'IMarker.php', <<<'PHP'
<?php declare(strict_types=1);
namespace MyApp;
interface IMarker {}
PHP);
		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'Bar.php', <<<'PHP'
<?php declare(strict_types=1);
namespace MyApp;
require_once __DIR__ . '/IMarker.php';
class Bar implements IMarker {}
PHP);

		$container = $this->makeContainer([]);
		$cm = new AbstractClassMapTestDouble2($container, $this->basedir);

		$map = $cm->exposeGetMap();

		$this->assertGreaterThan(0, $cm->generateCalls);
		$this->assertArrayHasKey('MyApp', $map);

		$this->assertArrayHasKey('interface', $map['MyApp']);
		$this->assertArrayHasKey('name', $map['MyApp']);

		$this->assertSame(\MyApp\Foo::class, $map['MyApp']['name']['foo']);
	}

	public function testGenerateSkipsWhenFileExistsAndNotRegenerate(): void {
		$container = $this->makeContainer([]);

		$cm = new AbstractClassMapTestDouble3($container);

		$cm->writeDummy();
		$cm->generate(false);

		$this->assertSame(0, $cm->scanCalls);
		$this->assertFileExists($cm->getFile());
	}

	public function testGenerateFromComposerClassMapPathWhenMethodExists(): void {
		$container = $this->makeContainer([]);

		$cm = new AbstractClassMapTestDouble4($container);


		$map = $cm->exposeGetMap();

		$this->assertArrayHasKey('AppX', $map);
		$this->assertSame(\Base3\Test\Core\ClassMapDemo::class, $map['AppX']['name']['demo']);
	}

	public function testGetInstancesCriteriaAndRetryGenerate(): void {
		$appDir = $this->basedir . DIRECTORY_SEPARATOR . 'AppA';
		@mkdir($appDir, 0777, true);

		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'IA.php', <<<'PHP'
<?php declare(strict_types=1);
namespace AppA;
interface IA {}
PHP);
		$this->writePhp($appDir . DIRECTORY_SEPARATOR . 'NamedThing.php', <<<'PHP'
<?php declare(strict_types=1);
namespace AppA;

use Base3\CoreFoundation\Api\IBase;
require_once __DIR__ . '/IA.php';

class NamedThing implements IBase, IA {
	public static function getName(): string { return 'named'; }
}
PHP);

		$container = $this->makeContainer([]);

		$cm = new AbstractClassMapTestDouble5($container, $this->basedir);

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

		$before = $cm->generateCalls;
		$none = $cm->getInstances(['app' => 'AppA', 'interface' => \Base3\Test\Core\IRetryMissing::class]);
		$this->assertIsArray($none);
		$this->assertGreaterThanOrEqual($before + 1, $cm->generateCalls);
	}

	public function testInstantiateCoversConstructorResolution(): void {










		$depA = new \Base3\Test\Core\DepA();

		$container = $this->makeContainer([
			\Base3\Test\Core\DepA::class => $depA,
			// for builtin by param name
			'name' => 'n1',
			// for paramName class resolution (non-sensical name "custom" key)
			'custom' => $depA,
		]);

		$cm = new AbstractClassMapTestDouble6($container);

		$noCtor = $cm->instantiate(\Base3\Test\Core\NoCtor::class);
		$this->assertInstanceOf(\Base3\Test\Core\NoCtor::class, $noCtor);

		$needsBuiltin = $cm->instantiate(\Base3\Test\Core\NeedsBuiltin::class);
		$this->assertInstanceOf(\Base3\Test\Core\NeedsBuiltin::class, $needsBuiltin);

		$needsClass = $cm->instantiate(\Base3\Test\Core\NeedsClassType::class);
		$this->assertInstanceOf(\Base3\Test\Core\NeedsClassType::class, $needsClass);
		$this->assertSame($depA, $needsClass->a);

		$needsParamName = $cm->instantiate(\Base3\Test\Core\NeedsByParamName::class);
		$this->assertInstanceOf(\Base3\Test\Core\NeedsByParamName::class, $needsParamName);

		$union = $cm->instantiate(\Base3\Test\Core\UnionNeeds::class);
		$this->assertInstanceOf(\Base3\Test\Core\UnionNeeds::class, $union);
		$this->assertInstanceOf(\Base3\Test\Core\DepA::class, $union->v);

		$unionDefault = $cm->instantiate(\Base3\Test\Core\UnionDefault::class);
		$this->assertInstanceOf(\Base3\Test\Core\UnionDefault::class, $unionDefault);

		$nullable = $cm->instantiate(\Base3\Test\Core\NullableDefault::class);
		$this->assertInstanceOf(\Base3\Test\Core\NullableDefault::class, $nullable);
		$this->assertNull($nullable->v);

		$needsMocked = $cm->instantiate(\Base3\Test\Core\NeedsMockedInterface::class);
		$this->assertInstanceOf(\Base3\Test\Core\NeedsMockedInterface::class, $needsMocked);
		$this->assertIsObject($needsMocked->m);

		// Abstract -> instantiate() should return null
		$this->assertNull($cm->instantiate(\Base3\Test\Core\AbstractX::class));
	}

	public function testCheckDependenciesReportsWritability(): void {
		$container = $this->makeContainer([]);

		$cm = new AbstractClassMapTestDouble7($container);

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

		$cm = new AbstractClassMapTestDouble8($container, $this->basedir);

		$classes = $cm->scanOnly();

		$found = array_map(fn($c) => $c['class'], $classes);

		$this->assertContains(\ScanApp\Ok::class, $found);
		$this->assertNotContains(\ScanApp\Abs::class, $found);
	}
}

require_once __DIR__ . '/AbstractClassMapTestDouble1.php';

require_once __DIR__ . '/AbstractClassMapTestDouble2.php';

require_once __DIR__ . '/AbstractClassMapTestDouble3.php';

require_once __DIR__ . '/AbstractClassMapTestDouble4.php';

require_once __DIR__ . '/AbstractClassMapTestDouble5.php';

require_once __DIR__ . '/AbstractClassMapTestDouble6.php';

require_once __DIR__ . '/AbstractClassMapTestDouble7.php';

require_once __DIR__ . '/AbstractClassMapTestDouble8.php';

require_once __DIR__ . '/ClassMapDemo.php';
require_once __DIR__ . '/IRetryMissing.php';
require_once __DIR__ . '/DepA.php';
require_once __DIR__ . '/DepB.php';
require_once __DIR__ . '/IMockMe.php';
require_once __DIR__ . '/NoCtor.php';
require_once __DIR__ . '/NeedsBuiltin.php';
require_once __DIR__ . '/NeedsClassType.php';
require_once __DIR__ . '/NeedsByParamName.php';
require_once __DIR__ . '/UnionNeeds.php';
require_once __DIR__ . '/UnionDefault.php';
require_once __DIR__ . '/NullableDefault.php';
require_once __DIR__ . '/NeedsMockedInterface.php';
require_once __DIR__ . '/AbstractX.php';
