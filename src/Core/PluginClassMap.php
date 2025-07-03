<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBase;
use Base3\Api\IPlugin;

class PluginClassMap extends AbstractClassMap {
    
	protected function getScanTargets(): array {
		return [
			["basedir" => DIR_SRC, "subdir" => "", "subns" => "Base3"],
			["basedir" => DIR_PLUGIN, "subdir" => "src", "subns" => ""]
		];
	}

	public function getPlugins() {
		$plugins = array();
		foreach ($this->map as $app => $appdata) {
			if (!isset($appdata['interface'])) continue;
			if (in_array(IPlugin::class, array_keys($appdata['interface']))) {
				$plugins[] = $app;
			}
		}
		return $plugins;
	}
}

