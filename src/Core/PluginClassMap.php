<?php declare(strict_types=1);

namespace Base3\Core;

class PluginClassMap {

	private $filename;
	private $map;

	public function __construct($filename = null) {

		if ($filename == null) $filename = DIR_TMP . "classmap.php";

		$this->filename = $filename;
		$this->generate();
		$this->map = require $this->filename;
	}

	public function generate($regenerate = false) {
		if (!$regenerate && file_exists($this->filename) && filesize($this->filename) > 0) return;

		if (!is_writable(DIR_TMP)) die('Directory /tmp has to be writable.');

		$fp = fopen($this->filename, "w");
		$str = "<?php return ";

		$this->map = array();

		$data = array(
			array("basedir" => DIR_SRC, "subdir" => ""),
			array("basedir" => DIR_PLUGIN, "subdir" => "src")
		);
		foreach ($data as $d) {
			$apps = $this->getEntries($d["basedir"]);
			foreach ($apps as $app) {
				$apppath = $d["basedir"] . DIRECTORY_SEPARATOR . $app;
				if (!empty($d["subdir"])) $apppath .= DIRECTORY_SEPARATOR . $d["subdir"];
				if (!is_dir($apppath)) continue;
				$classes = array();
				$this->getClasses($classes, $d["basedir"], $app, $d["subdir"]);
				foreach ($classes as $c) {
					foreach ($c["interfaces"] as $interface) {
						$this->map[$app]["interface"][$interface][] = $c["class"];
						if ($interface == "Base3\\Api\\IBase") {
							$instance = new $c["class"];
							$name = $instance->getName();
							$this->map[$app]["name"][$name] = $c["class"];
						}
					}
				}
			}
		}

		$str .= var_export($this->map, true);
		$str .= ";\n";
		fwrite($fp, $str);
		fclose($fp);
	}

	public function getApps() {
		return array_keys($this->map);
	}

	public function getPlugins() {
		$plugins = array();
		foreach ($this->map as $app => $appdata) {
			if (!isset($appdata['interface'])) continue;
			if (in_array('Base3\\Api\\IPlugin', array_keys($appdata['interface']))) $plugins[] = $app;
		}
		return $plugins;
	}

	public function &getInstancesByInterface($interface) {
		$instances = array();
		foreach ($this->map as $app => $m) {
			$is = $this->getInstancesByAppInterface($app, $interface, true);
			$instances = array_merge($instances, $is);
		}
		return $instances;
	}

	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		$instances = array();
		if (isset($this->map[$app]) && isset($this->map[$app]["interface"][$interface])) {
			$cs = $this->map[$app]["interface"][$interface];
			foreach ($cs as $c) $instances[] = new $c;
			return $instances;
		}

		if ($retry) return $instances;
		$this->generate(true);
		return $this->getInstancesByAppInterface($app, $interface, true);
	}

	public function &getInstanceByAppName($app, $name, $retry = false) {
		$instance = null;
		if (isset($this->map[$app]) && isset($this->map[$app]["name"][$name])) {
			$c = $this->map[$app]["name"][$name];
			if (class_exists($c)) {  // alternatively regenerate classmap
				$instance = new $c;
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByAppName($app, $name, true);
	}

	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$instance = null;
		if (is_array($this->map)) {
			foreach ($this->map as $appdata) {
				if (!isset($appdata["name"])) continue;
				foreach ($appdata["name"] as $n => $c) {
					if ($n != $name || !class_exists($c)) continue;
					// TODO check if class implements given interface
					$instance = new $c;
					return $instance;
				}
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByInterfaceName($interface, $name, true);
	}

	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		if (!strlen($app)) return $this->getInstanceByInterfaceName($interface, $name);

		$instance = null;
		if (is_array($this->map) && isset($this->map[$app]) && isset($this->map[$app]["name"][$name]) && isset($this->map[$app]["interface"][$interface])) {
			$c = $this->map[$app]["name"][$name];
			if (!in_array($c, $this->map[$app]["interface"][$interface])) return null;
			if (class_exists($c)) {  // alternatively regenerate classmap
				$instance = new $c;
				return $instance;
			}
		}

		if ($retry) return $instance;
		$this->generate(true);
		return $this->getInstanceByAppInterfaceName($app, $interface, $name, true);
	}

	private function getClasses(&$classes, $basedir, $app, $subdir = "", $path = "") {
		$fullpath = $basedir . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $path;
		$entries = $this->getEntries($fullpath);
		foreach ($entries as $entry) {
			$fullentry = $fullpath . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($fullentry)) {
				$this->getClasses($classes, $basedir, $app, $subdir, $path . DIRECTORY_SEPARATOR . $entry);
			} else {
				if (strrchr($entry, ".") != ".php" || strchr($entry, ".") != ".php") continue;  // nur ein Punkt im Dateinamen!

				require_once($fullentry);

				$nsparts = array($app);
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

	private function getEntries($path) {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$entries = array();
		$handle = opendir($path);
		while ($entry = readdir($handle)) {
			if ($entry == "." || $entry == "..") continue;
			if (substr($entry, 0, 1) == "_") continue;
			if (substr($entry, 0, 1) == ".") continue;
			$entries[] = $entry;
		}
		closedir($handle);
		return $entries;
	}
}
