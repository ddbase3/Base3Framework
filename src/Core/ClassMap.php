<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;

class ClassMap extends AbstractClassMap {

	private $path = DIR_SRC;

	public function generate($regenerate = false) {
		if (!$regenerate && file_exists($this->filename)) return;

		if (!is_writable(DIR_TMP)) die('Directory /tmp has to be writable.');

		$this->map = array();
		$apps = $this->getEntries($this->path);
		foreach ($apps as $app) {
			$apppath = $this->path . DIRECTORY_SEPARATOR . $app;
			if (!is_dir($apppath)) continue;
			$classes = array();
			$this->getClasses($classes, $apppath);
			foreach ($classes as $c) {
				foreach ($c['interfaces'] as $interface) {
					$this->map[$app]['interface'][$interface][] = $c['class'];

					if ($interface !== IBase::class) continue;
					if (!method_exists($c['class'], 'getName')) continue;

					try {
						$name = $c['class']::getName();
					} catch (\Throwable $e) {
						continue;  //ignore failing implementations
					}
					$this->map[$app]['name'][$name] = $c['class'];
				}
			}
		}

		$this->writeClassMap();
	}

	// Private methods

	private function getClasses(&$classes, $path) {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$entries = $this->getEntries($path);
		foreach ($entries as $entry) {
			$fullentry = $path . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($fullentry)) {
				$this->getClasses($classes, $fullentry);
			} else {
				if (strrchr($entry, ".") != ".php" || strchr($entry, ".") != ".php") continue;  // nur ein Punkt im Dateinamen!

				require_once($fullentry);

				$namespace = "Base3\\" . substr(str_replace(DIRECTORY_SEPARATOR, "\\", $path), strlen($this->path) + 1);
				$classname = $namespace . "\\" . substr($entry, 0, strrpos($entry, "."));

				if (!class_exists($classname, false)) continue;

				$rc = new \ReflectionClass($classname);
				if ($rc->isAbstract()) continue;

				$interfaces = class_implements($classname);

				$classes[] = array("file" => $fullentry, "class" => $classname, "interfaces" => $interfaces);
			}
		}
	}
}
