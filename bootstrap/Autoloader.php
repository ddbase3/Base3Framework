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

namespace Base3\Bootstrap;

use RuntimeException;

final class Autoloader {

	/** @var array<string, string> */
	private static array $prefixes = [];
	private static bool $registered = false;

	public static function register(): void {
		if (self::$registered) return;

		if (defined('DIR_TEST')) {
			self::addNamespace('Base3\\Test\\', (string) DIR_TEST);
		}

		if (defined('DIR_PLUGIN') && is_dir((string) DIR_PLUGIN)) {
			foreach (glob(rtrim((string) DIR_PLUGIN, '/\\') . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $moduleRoot) {
				$manifestPath = $moduleRoot . DIRECTORY_SEPARATOR . 'base3.json';
				if (!is_file($manifestPath)) continue;
				$manifest = json_decode((string) file_get_contents($manifestPath), true);
				if (!is_array($manifest) || !is_string($manifest['namespace'] ?? null) || trim($manifest['namespace']) === '') {
					throw new RuntimeException('Invalid module namespace: ' . $manifestPath);
				}
				$namespace = trim($manifest['namespace'], '\\');
				if (!str_starts_with($namespace, 'Base3\\')) {
					throw new RuntimeException('Module namespace must start with Base3: ' . $manifestPath);
				}
				self::addNamespace($namespace . '\\Test\\', $moduleRoot . '/test');
				self::addNamespace($namespace . '\\', $moduleRoot . '/src');
			}
		}

		self::sortPrefixes();
		spl_autoload_register([self::class, 'autoload']);
		self::$registered = true;
	}

	private static function addNamespace(string $prefix, string $dir): void {
		$path = realpath($dir);
		if ($path !== false) self::$prefixes[$prefix] = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
	}

	private static function sortPrefixes(): void {
		uksort(self::$prefixes, static fn($a, $b) => strlen($b) <=> strlen($a));
	}

	/** For integrations that locate an existing module at a non-standard path. */
	public static function registerPlugin(string $pluginNamespace, string $baseDir): void {
		self::addNamespace(rtrim($pluginNamespace, '\\') . '\\', $baseDir);
		self::sortPrefixes();
	}

	private static function autoload(string $class): void {
		foreach (self::$prefixes as $prefix => $baseDir) {
			if (!str_starts_with($class, $prefix)) continue;
			$relative = substr($class, strlen($prefix));
			$path = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
			if (getenv('DEBUG') === '2') echo "Autoload: $class => $path\n";
			if (is_file($path)) require_once $path;
			return;
		}
	}
}
