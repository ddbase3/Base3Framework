<?php

declare(strict_types=1);

/*
 * PHPStan bootstrap (no Base3 autoloader)
 * - defines required directory constants
 * - registers a safe autoloader for static analysis
 */

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
	throw new RuntimeException('Cannot resolve project root directory.');
}

define('DIR_ROOT', rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_TEST', DIR_ROOT . 'test' . DIRECTORY_SEPARATOR);
define('DIR_TMP', DIR_ROOT . 'tmp' . DIRECTORY_SEPARATOR);

define('Base3\\Core\\DIR_ROOT', DIR_ROOT);
define('Base3\\Core\\DIR_SRC', DIR_SRC);
define('Base3\\Core\\DIR_PLUGIN', DIR_PLUGIN);
define('Base3\\Core\\DIR_TEST', DIR_TEST);
define('Base3\\Core\\DIR_TMP', DIR_TMP);

/* Optional: Composer autoload (if present) */
$composerAutoload = DIR_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($composerAutoload)) {
	require_once $composerAutoload;
}

spl_autoload_register(static function (string $class): void {
	$class = ltrim($class, '\\');
	$parts = explode('\\', $class);

	/* Base3\Foo\Bar -> src/Foo/Bar.php */
	if (($parts[0] ?? '') === 'Base3') {
		$relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1)) . '.php';
		$file = DIR_SRC . $relative;
		if (is_file($file)) {
			require_once $file;
		}
		return;
	}

	/* PluginNamespace\Foo\Bar -> plugin/PluginNamespace/src/Foo/Bar.php */
	$plugin = $parts[0] ?? '';
	if ($plugin !== '') {
		$relative = implode(DIRECTORY_SEPARATOR, array_slice($parts, 1)) . '.php';
		$file = DIR_PLUGIN . $plugin . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative;
		if (is_file($file)) {
			require_once $file;
		}
	}
}, true, true);
