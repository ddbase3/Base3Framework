<?php declare(strict_types=1);

class Autoloader {

	/**
	 * Register new autoloader
	 * @return void
	 */
	public static function register() {
		// Autoloader for classes
		spl_autoload_register(__NAMESPACE__ .'\Autoloader::__autoloadClass');
	}

	/**
	 * Class autoloader
	 * @param $class
	 * @return void
	 */
	public static function __autoloadClass($class) {

                // Core
		$filePath = self::_transformClassNameToFilename($class, DIR_SRC);
		if (file_exists($filePath)) {
			require $filePath;
			return;
		}

		// Plugin
		$filePath = self::_transformPluginClassNameToFilename($class, DIR_PLUGIN);
		if (file_exists($filePath)) {
			require $filePath;
			return;
		}
	}

	/**
	 * Function loader
	 * @param $func
	 */
	public static function loadFunction($func) {

		// Core
		$filePath = self::_transformClassNameToFilename($func, DIR_SRC);
		if (file_exists($filePath)) {
			require_once $filePath;
			return;
		}

		// Plugin
		$filePath = self::_transformPluginClassNameToFilename($func, DIR_PLUGIN);
		if (file_exists($filePath)) {
			require_once $filePath;
			return;
		}
	}

	/**
	 * Transform class namespace to class filename
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
	 * @param $className
	 * @param $directory
	 *
	 * @return string
	 */
	private static function _transformClassNameToFilename($className, $directory) {
		$className	= ltrim($className, '\\');
		$fileName	= '';
		if ($lastNsPos	= strrpos($className, '\\')) {
			$namespace	= substr($className, 0, $lastNsPos);
			$className	= substr($className, $lastNsPos + 1);
			$fileName	= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		return $directory . $fileName;
	}

	/**
	 * Transform plugin class namespace to class filename
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
	 * @param $className
	 * @param $directory
	 *
	 * @return string
	 */
	private static function _transformPluginClassNameToFilename($className, $directory) {
		$className	= ltrim($className, '\\');
		$fileName	= '';
		if ($lastNsPos	= strrpos($className, '\\')) {
			$namespace	= substr($className, 0, $lastNsPos);
			$className	= substr($className, $lastNsPos + 1);
			$fileName	= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		// plugin classes in 'src' directory
		if ($firstDsPos = strpos($fileName, DIRECTORY_SEPARATOR)) {
			$fileName = substr($fileName, 0, $firstDsPos) . DIRECTORY_SEPARATOR . 'src' . substr($fileName, $firstDsPos);
		}

		return $directory . $fileName;
	}
}
