<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IPlugin;

class PluginClassMap extends AbstractClassMap {

	protected function getScanTargets(): array {
		return [
			["basedir" => DIR_SRC, "subdir" => "", "subns" => "Base3"],
			["basedir" => DIR_PLUGIN, "subdir" => "src", "subns" => ""]
		];
	}

	public function getPlugins(): array {
		$plugins = [];

		foreach ($this->getMap() as $app => $appdata) {
			if (!isset($appdata['interface'])) continue;
			if (array_key_exists(IPlugin::class, $appdata['interface'])) {
				$plugins[] = $app;
			}
		}

		return $plugins;
	}
}

