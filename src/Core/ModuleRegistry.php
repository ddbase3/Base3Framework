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

namespace Base3\Core;

use Base3\Api\IModuleRegistry;
use RuntimeException;

/**
 * Module registry for the standard standalone BASE3 layout.
 */
final class ModuleRegistry implements IModuleRegistry {

	/** @var array<string,string>|null */
	private ?array $modulePaths = null;

	public function getModuleNames(): array {
		return array_keys($this->getModulePaths());
	}

	public function getModulePath(string $name): ?string {
		$paths = $this->getModulePaths();
		return $paths[$name] ?? null;
	}

	public function requireModulePath(string $name): string {
		$path = $this->getModulePath($name);
		if ($path === null) {
			throw new RuntimeException('Required BASE3 module not found: ' . $name);
		}

		return $path;
	}

	/**
	 * @return array<string,string>
	 */
	private function getModulePaths(): array {
		if ($this->modulePaths !== null) {
			return $this->modulePaths;
		}

		$paths = [];
		$namespaces = [];

		if (defined('DIR_ROOT')) {
			$this->registerModule((string) DIR_ROOT, $paths, $namespaces);
		}

		if (defined('DIR_PLUGIN') && is_dir((string) DIR_PLUGIN)) {
			foreach (glob(rtrim((string) DIR_PLUGIN, '/\\') . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $moduleRoot) {
				$this->registerModule($moduleRoot, $paths, $namespaces);
			}
		}

		ksort($paths, SORT_NATURAL | SORT_FLAG_CASE);
		$this->modulePaths = $paths;
		return $this->modulePaths;
	}

	/**
	 * @param array<string,string> $paths
	 * @param array<string,string> $namespaces
	 */
	private function registerModule(string $moduleRoot, array &$paths, array &$namespaces): void {
		$moduleRoot = rtrim($moduleRoot, '/\\');
		$manifestFile = $moduleRoot . DIRECTORY_SEPARATOR . 'base3.json';
		if (!is_file($manifestFile)) {
			return;
		}

		$json = file_get_contents($manifestFile);
		if ($json === false) {
			throw new RuntimeException('Could not read BASE3 manifest: ' . $manifestFile);
		}

		try {
			$manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $e) {
			throw new RuntimeException('Invalid BASE3 manifest JSON: ' . $manifestFile . ': ' . $e->getMessage(), 0, $e);
		}

		if (!is_array($manifest) || ($manifest['manifestVersion'] ?? null) !== 1) {
			throw new RuntimeException('Invalid BASE3 manifest: ' . $manifestFile);
		}

		$name = $this->requireManifestString($manifest, 'name', $manifestFile);
		$namespace = $this->requireManifestString($manifest, 'namespace', $manifestFile);
		$this->requireManifestString($manifest, 'version', $manifestFile);

		if (isset($paths[$name])) {
			throw new RuntimeException('Duplicate BASE3 module name "' . $name . '": ' . $paths[$name] . ' and ' . $moduleRoot);
		}

		$namespaceKey = strtolower($namespace);
		if (isset($namespaces[$namespaceKey])) {
			throw new RuntimeException('Duplicate BASE3 module namespace "' . $namespace . '": ' . $namespaces[$namespaceKey] . ' and ' . $moduleRoot);
		}

		$paths[$name] = $moduleRoot;
		$namespaces[$namespaceKey] = $moduleRoot;
	}

	private function requireManifestString(array $manifest, string $key, string $manifestFile): string {
		$value = $manifest[$key] ?? null;
		if (!is_string($value) || trim($value) === '') {
			throw new RuntimeException('Missing or invalid BASE3 manifest field "' . $key . '": ' . $manifestFile);
		}

		return trim($value);
	}
}
