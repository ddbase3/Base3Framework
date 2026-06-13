<?php declare(strict_types=1);

namespace Base3\Core;

use PHPUnit\Framework\TestCase;

final class AssetResolverTest extends TestCase {

	private ?string $tmpRoot = null;

	protected function tearDown(): void {
		if ($this->tmpRoot !== null && is_dir($this->tmpRoot)) {
			$this->rmDirRecursive($this->tmpRoot);
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

	private function makeTmpRoot(): string {
		$base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
		$dir = $base . DIRECTORY_SEPARATOR . 'base3-assetresolver-' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;

		if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
			self::fail('Could not create temp directory: ' . $dir);
		}

		$this->tmpRoot = rtrim($dir, DIRECTORY_SEPARATOR);
		return $this->tmpRoot;
	}

	private function ensureDirRootConstant(string $root): void {
		// AssetResolver uses unqualified DIR_ROOT in Base3\Core namespace
		$ns = __NAMESPACE__ . '\\DIR_ROOT';
		if (!defined($ns)) {
			define($ns, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
		}

		// global fallback (harmless)
		if (!defined('DIR_ROOT')) {
			define('DIR_ROOT', rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
		}
	}

	public function testResolveReturnsOriginalPathIfNotPluginPrefixed(): void {
		$this->ensureDirRootConstant($this->makeTmpRoot());

		$resolver = new AssetResolver();

		self::assertSame('assets/app.css', $resolver->resolve('assets/app.css'));
		self::assertSame('/assets/app.css', $resolver->resolve('/assets/app.css'));
	}

	public function testResolveReturnsOriginalPathIfPluginPathIsMalformed(): void {
		$this->ensureDirRootConstant($this->makeTmpRoot());

		$resolver = new AssetResolver();

		// too short (count < 4) -> unchanged
		self::assertSame('plugin/Foo/assets', $resolver->resolve('plugin/Foo/assets'));

		// parts[2] must be "assets" -> unchanged
		self::assertSame('plugin/Foo/static/js/app.js', $resolver->resolve('plugin/Foo/static/js/app.js'));

		// note: trailing slash produces count == 4 => NOT malformed for this implementation
		// it resolves to "assets/<Plugin>/?t=000000"
		self::assertSame('assets/Foo/?t=000000', $resolver->resolve('plugin/Foo/assets/'));
	}

	public function testResolveBuildsPublicPathAndReturnsZeroHashWhenFileDoesNotExist(): void {
		$root = $this->makeTmpRoot();
		$this->ensureDirRootConstant($root);

		$resolver = new AssetResolver();

		$path = 'plugin/Demo/assets/js/app.js';
		$resolved = $resolver->resolve($path);

		self::assertSame('assets/Demo/js/app.js?t=000000', $resolved);
	}

	public function testResolveAddsHashWhenFileExists(): void {
		$root = $this->makeTmpRoot();
		$this->ensureDirRootConstant($root);

		$resolver = new AssetResolver();

		$path = 'plugin/Demo/assets/js/app.js';

		// IMPORTANT: AssetResolver builds $realfile as DIR_ROOT . implode(DIRECTORY_SEPARATOR, $parts)
		// i.e. without an extra separator between DIR_ROOT and the first part if DIR_ROOT has no trailing slash.
		// So we create the file exactly where AssetResolver will look for it.
		$parts = explode('/', $path);
		$realPath = constant(__NAMESPACE__ . '\\DIR_ROOT') . implode(DIRECTORY_SEPARATOR, $parts);

		@mkdir(dirname($realPath), 0777, true);
		file_put_contents($realPath, 'console.log("x");');

		$expectedHash = substr(md5_file($realPath), 0, 6);

		$resolved = $resolver->resolve($path);

		self::assertSame('assets/Demo/js/app.js?t=' . $expectedHash, $resolved);
	}

	public function testResolveKeepsSubdirectories(): void {
		$root = $this->makeTmpRoot();
		$this->ensureDirRootConstant($root);

		$path = 'plugin/MyPlugin/assets/images/icons/edit.svg';

		$resolver = new AssetResolver();
		$resolved = $resolver->resolve($path);

		self::assertStringStartsWith('assets/MyPlugin/images/icons/edit.svg?t=', $resolved);
	}
}
