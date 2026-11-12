<?php declare(strict_types=1);

namespace Base3\Diagnostics\Inspection;

use Base3\Api\IModuleRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Throwable;

/** Read-only source inventory for the existing BASE3 diagnostic checks. */
final class ModuleSourceInventory {

	public function __construct(
		private readonly IModuleRegistry $modules,
		private readonly ?string $frameworkRoot = null,
		private readonly ?string $pluginsRoot = null
	) {}

	/**
	 * @return array{roots:list<string>,errors:list<string>,coverage:bool}
	 */
	public function roots(): array {
		$roots = [];
		$errors = [];
		$coverage = true;

		try {
			foreach($this->modules->getModuleNames() as $name) {
				$path = $this->modules->getModulePath($name);
				if(!is_string($path) || !is_dir($path)) {
					$errors[] = 'Registry module missing directory: ' . $name;
					continue;
				}
				$roots[] = $path;
			}
		} catch(Throwable $exception) {
			$errors[] = 'Module registry could not enumerate the complete installation.';
			$coverage = false;
		}

		$framework = $this->frameworkRoot ?? (defined('DIR_ROOT') ? (string)constant('DIR_ROOT') : null);
		if(is_string($framework) && is_dir($framework) && (
			is_file(rtrim($framework, '/\\') . '/base3.json') ||
			is_dir(rtrim($framework, '/\\') . '/src/Diagnostics')
		)) {
			$roots[] = $framework;
		}

		$plugins = $this->pluginsRoot ?? (defined('DIR_PLUGIN') ? (string)constant('DIR_PLUGIN') : null);
		if(is_string($plugins) && is_dir($plugins)) {
			foreach(glob(rtrim($plugins, '/\\') . '/*', GLOB_ONLYDIR) ?: [] as $path) {
				$roots[] = $path;
			}
		} else {
			$coverage = false;
			if(!$roots) $errors[] = 'No module roots can be inspected.';
		}

		$unique = [];
		foreach($roots as $root) {
			$path = realpath($root);
			if($path !== false) {
				$unique[$path] = $path;
			}
		}
		sort($unique, SORT_STRING);
		return ['roots' => array_values($unique), 'errors' => $errors, 'coverage' => $coverage];
	}

	/**
	 * @return array{manifest:array<string,mixed>|null,errors:list<string>}
	 */
	public function manifest(string $root): array {
		$file = $root . '/base3.json';
		if(!is_file($file)) {
			return ['manifest' => null, 'errors' => ['Missing base3.json in ' . basename($root)]];
		}
		$contents = file_get_contents($file);
		if($contents === false) {
			return ['manifest' => null, 'errors' => ['Cannot read base3.json in ' . basename($root)]];
		}
		try {
			$data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		} catch(\JsonException $exception) {
			return ['manifest' => null, 'errors' => ['Invalid base3.json JSON in ' . basename($root)]];
		}
		if(!is_array($data) || array_is_list($data)) {
			return ['manifest' => null, 'errors' => ['Invalid manifest object in ' . basename($root)]];
		}
		$errors = [];
		if(($data['manifestVersion'] ?? null) !== 1) {
			$errors[] = 'Unsupported manifestVersion in ' . basename($root);
		}
		foreach(['name', 'namespace', 'version'] as $key) {
			if(!is_string($data[$key] ?? null) || trim($data[$key]) === '') {
				$errors[] = 'Missing or invalid ' . $key . ' in ' . basename($root);
			}
		}
		if(is_string($data['namespace'] ?? null) && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $data['namespace'])) {
			$errors[] = 'Invalid namespace in ' . basename($root);
		}
		if(is_string($data['version'] ?? null) && !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/D', $data['version'])) {
			$errors[] = 'Invalid module version in ' . basename($root);
		}
		if(isset($data['dependencies']) && (!is_array($data['dependencies']) || array_is_list($data['dependencies']) && $data['dependencies'] !== [])) {
			$errors[] = 'Invalid dependencies object in ' . basename($root);
		} elseif(is_array($data['dependencies'] ?? null)) {
			foreach($data['dependencies'] as $name => $constraint) {
				if(!is_string($name) || trim($name) === '' || !is_string($constraint) || trim($constraint) === '') {
					$errors[] = 'Invalid dependency entry in ' . basename($root);
				}
			}
		}
		return ['manifest' => $data, 'errors' => $errors];
	}

	/** @return list<string> */
	public function sourceFiles(string $root): array {
		$dir = $root . '/src';
		if(!is_dir($dir)) {
			return [];
		}
		$files = [];
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $entry) {
			if($entry->isFile() && strtolower($entry->getExtension()) === 'php') {
				$files[] = $entry->getPathname();
			}
		}
		sort($files, SORT_STRING);
		return $files;
	}

	public static function relativeFile(string $root, string $file): string {
		return basename($root) . '/' . ltrim(substr($file, strlen(rtrim($root, '/\\'))), '/\\');
	}

}
