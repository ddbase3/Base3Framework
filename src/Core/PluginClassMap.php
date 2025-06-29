<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBase;
use Base3\Api\IPlugin;

class PluginClassMap extends AbstractClassMap {

	public function generate($regenerate = false) {

		if (!$regenerate && file_exists($this->filename) && filesize($this->filename) > 0) return;

		if (!is_writable(DIR_TMP)) die('Directory /tmp has to be writable.');

		$this->map = array();

		$data = array(
			array("basedir" => DIR_SRC, "subdir" => "", "subns" => "Base3"),
			array("basedir" => DIR_PLUGIN, "subdir" => "src", "subns" => "")
		);
		foreach ($data as $d) {
			$apps = $this->getEntries($d["basedir"]);
			foreach ($apps as $app) {
				$apppath = $d["basedir"] . DIRECTORY_SEPARATOR . $app;
				if (!empty($d["subdir"])) $apppath .= DIRECTORY_SEPARATOR . $d["subdir"];
				if (!is_dir($apppath)) continue;
				$classes = [];
				$this->getClasses($classes, $d["basedir"], $app, $d["subdir"], $d["subns"]);
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
		}

		$this->writeClassMap();
	}

	public function getPlugins() {
		$plugins = array();
		foreach ($this->map as $app => $appdata) {
			if (!isset($appdata['interface'])) continue;
			if (in_array(IPlugin::class, array_keys($appdata['interface']))) $plugins[] = $app;
		}
		return $plugins;
	}

	// Private methods

	private function getClasses(&$classes, $basedir, $app, $subdir = "", $subns = "", $path = "") {
		$fullpath = $basedir . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $path;
		$entries = $this->getEntries($fullpath);
		foreach ($entries as $entry) {
			$fullentry = $fullpath . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($fullentry)) {
				$this->getClasses($classes, $basedir, $app, $subdir, $subns, $path . DIRECTORY_SEPARATOR . $entry);
			} else {
				if (strrchr($entry, ".") != ".php" || strchr($entry, ".") != ".php") continue;  // nur ein Punkt im Dateinamen!

				require_once($fullentry);

				$nsparts = [];
				if (!empty($subns)) $nsparts[] = $subns;
				$nsparts[] = $app;
				$pathparts = explode(DIRECTORY_SEPARATOR, $path);
				foreach ($pathparts as $pp) if (!empty($pp)) $nsparts[] = $pp;
				$namespace = implode("\\", $nsparts);
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
