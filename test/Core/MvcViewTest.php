<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class MvcViewTest extends TestCase {

	private ?string $tmpDir = null;

	protected function tearDown(): void {
		$this->resetServiceLocatorSingletons();

		if ($this->tmpDir !== null && is_dir($this->tmpDir)) {
			$this->rmDirRecursive($this->tmpDir);
		}
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
		$dir = $base . DIRECTORY_SEPARATOR . 'base3-mvcview-' . bin2hex(random_bytes(8));
		if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
			self::fail('Could not create temp directory: ' . $dir);
		}
		$this->tmpDir = $dir;
		return $dir;
	}

	public function testSetPathTrimsTrailingDirectorySeparator(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'tpl', 0777, true);

		$templateName = 'default';
		$templateFile = $dir . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $templateName;
		file_put_contents($templateFile, '<?php echo "OK";');

		$view = new MvcView();
		$view->setPath($dir . DIRECTORY_SEPARATOR);

		self::assertSame('OK', $view->loadTemplate());
	}

	public function testAssignAndLoadTemplateRendersIncludedFileOutput(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'tpl', 0777, true);

		$templateName = 'default';
		$templateFile = $dir . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $templateName;

		// include runs inside object context, so $this is available and can access private props
		file_put_contents($templateFile, '<?php echo "Hello " . $this->_["name"];');

		$view = new MvcView();
		$view->setPath($dir);
		$view->assign('name', 'World');

		self::assertSame('Hello World', $view->loadTemplate());
	}

	public function testSetTemplateChangesWhichTemplateIsLoaded(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'tpl', 0777, true);

		file_put_contents($dir . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'one', 'ONE');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'two', 'TWO');

		$view = new MvcView();
		$view->setPath($dir);

		$view->setTemplate('one');
		self::assertSame('ONE', $view->loadTemplate());

		$view->setTemplate('two');
		self::assertSame('TWO', $view->loadTemplate());
	}

	public function testLoadTemplateReturnsErrorMessageWhenFileDoesNotExist(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'tpl', 0777, true);

		$view = new MvcView();
		$view->setPath($dir);
		$view->setTemplate('missing');

		$expectedFile = $dir . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'missing';

		self::assertSame('Unable to find template - ' . $expectedFile, $view->loadTemplate());
	}

	public function testLoadBricksWithExplicitLanguageLoadsIniAndAssignsBricks(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'common', 0777, true);

		$ini = <<<INI
[common]
hello = "Hello"
bye = "Bye"
INI;

		file_put_contents($dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'en.ini', $ini);

		$view = new MvcView();
		$view->setPath($dir);

		$view->loadBricks('common', 'en');

		$bricks = $view->getBricks('common');
		self::assertIsArray($bricks);
		self::assertSame('Hello', $bricks['hello']);
		self::assertSame('Bye', $bricks['bye']);
	}

	public function testLoadBricksMergesWithAlreadyAssignedBricks(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'set1', 0777, true);

		file_put_contents(
			$dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'set1' . DIRECTORY_SEPARATOR . 'en.ini',
			"[set1]\na = \"A\"\n"
		);

		$view = new MvcView();
		$view->setPath($dir);

		$existing = [
			'other' => ['x' => 'X'],
		];
		$view->assign('bricks', $existing);

		$view->loadBricks('set1', 'en');

		self::assertSame(['x' => 'X'], $view->getBricks('other'));
		self::assertSame(['a' => 'A'], $view->getBricks('set1'));
	}

	public function testGetBricksReturnsNullWhenNotLoadedOrSetMissing(): void {
		$view = new MvcView();

		self::assertNull($view->getBricks('anything'));

		$view->assign('bricks', [
			'known' => ['k' => 'v'],
		]);

		self::assertNull($view->getBricks('missing'));
		self::assertSame(['k' => 'v'], $view->getBricks('known'));
	}

	public function testLoadBricksWithoutLanguageUsesServiceLocatorLanguageService(): void {
		$dir = $this->makeTmpDir();
		@mkdir($dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'ui', 0777, true);

		file_put_contents(
			$dir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'de.ini',
			"[ui]\nwelcome = \"Willkommen\"\n"
		);

		// Provide ServiceLocator external instance with a "language" service.
		// Must be SHARED, otherwise ServiceLocator will try to instantiate it via new Class() without args.
		$this->resetServiceLocatorSingletons();
		$sl = new ServiceLocator();
		$sl->set('language', new DummyLanguageService('de'), IContainer::SHARED);
		ServiceLocator::useInstance($sl);

		$view = new MvcView();
		$view->setPath($dir);

		$view->loadBricks('ui'); // language omitted -> must come from service locator

		$bricks = $view->getBricks('ui');
		self::assertIsArray($bricks);
		self::assertSame('Willkommen', $bricks['welcome']);
	}
}

final class DummyLanguageService {

	private string $lang;

	public function __construct(string $lang) {
		$this->lang = $lang;
	}

	public function getLanguage(): string {
		return $this->lang;
	}
}
