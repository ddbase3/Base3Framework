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

class Autoloader
{
	/** @var array<string, string> Namespace prefixes → directory mapping */
	private static array $prefixes = [];

	/** @var bool Autoloader already registered? */
	private static bool $registered = false;

	/**
	 * Register the autoloader (only once)
	 */
	public static function register(): void
	{
		if (self::$registered) {
			return;
		}

		// Base namespaces
		self::addNamespace('Base3\\', DIR_SRC);
		self::addNamespace('Base3\\Test\\', DIR_TEST);

		// Add plugins dynamically
		foreach (glob(DIR_PLUGIN . '*', GLOB_ONLYDIR) as $pluginPath) {
			$pluginName = basename($pluginPath);
			self::addNamespace($pluginName . '\\', $pluginPath . '/src');
			self::addNamespace($pluginName . '\\Test\\', $pluginPath . '/test');
		}

		// Sort prefixes by length (important for overlapping prefixes)
		uksort(self::$prefixes, fn($a, $b) => strlen($b) <=> strlen($a));

		spl_autoload_register([self::class, 'autoload']);
		self::$registered = true;
	}

	/**
	 * Add a namespace directory (if it exists)
	 */
	private static function addNamespace(string $prefix, string $dir): void
	{
		$path = realpath($dir);
		if ($path !== false) {
			self::$prefixes[$prefix] = rtrim($path, '/') . '/';
		}
	}

	public static function registerPlugin(string $pluginNamespace, string $baseDir): void
	{
		self::$prefixes[$pluginNamespace] = $baseDir;
	}

	/**
	 * PSR-4 compatible autoloader
	 */
	private static function autoload(string $class): void
	{
		foreach (self::$prefixes as $prefix => $baseDir) {
			if (str_starts_with($class, $prefix)) {
				$relativeClass = substr($class, strlen($prefix));
				$file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

				if (getenv('DEBUG') === '2') {
					echo "Autoload: $class → $file\n";
				}

				if (file_exists($file)) {
					require $file;
				}

				return;
			}
		}

		if (getenv('DEBUG') === '1') {
			echo "Autoload: $class → NOT FOUND\n";
		}
	}
}
