<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Temporary source tree containing complete synthetic BASE3 modules. */
final class DiagnosisSourceFixture {

	public readonly string $root;
	public readonly string $frameworkRoot;
	public readonly string $pluginsRoot;

	public function __construct() {
		$this->root = sys_get_temp_dir() . '/base3-diag-' . bin2hex(random_bytes(8));
		$this->frameworkRoot = $this->root . '/framework';
		$this->pluginsRoot = $this->root . '/plugins';
		mkdir($this->frameworkRoot, 0777, true);
		mkdir($this->pluginsRoot, 0777, true);
	}

	/**
	 * @param array<string,string> $dependencies
	 * @param array<string,string> $files Relative src paths to full PHP contents
	 */
	public function module(string $name, string $namespace, string $version = '1.0.0', array $dependencies = [], array $files = []): string {
		$root = $this->pluginsRoot . '/' . $name;
		if(!is_dir($root . '/src')) mkdir($root . '/src', 0777, true);
		$this->writeManifest($name, [
			'manifestVersion' => 1,
			'name' => $name,
			'namespace' => $namespace,
			'version' => $version,
			'dependencies' => (object)$dependencies
		]);
		foreach($files as $path => $source) {
			$this->writeSource($name, $path, $source);
		}
		return $root;
	}

	/** @param array<string,mixed> $data */
	public function writeManifest(string $module, array $data): void {
		$root = $this->pluginsRoot . '/' . $module;
		if(!is_dir($root)) mkdir($root, 0777, true);
		file_put_contents($root . '/base3.json', json_encode($data, JSON_THROW_ON_ERROR));
	}

	public function writeSource(string $module, string $relative, string $source): void {
		$file = $this->pluginsRoot . '/' . $module . '/src/' . $relative;
		if(!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
		file_put_contents($file, $source);
	}

	public function registry(): DiagnosisTestModuleRegistry {
		$paths = [];
		foreach(glob($this->pluginsRoot . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
			if(is_file($directory . '/base3.json')) $paths[basename($directory)] = $directory;
		}
		return new DiagnosisTestModuleRegistry($paths);
	}

	public function cleanup(): void {
		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach($items as $item) {
			if($item->isDir()) rmdir($item->getPathname());
			else unlink($item->getPathname());
		}
		rmdir($this->root);
	}

}
