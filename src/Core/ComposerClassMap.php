<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBase;

class ClassMapComposer extends AbstractClassMap {

	protected function generateFromComposerClassMap(): void {
		$classmap = require dirname(__DIR__, 3) . '/vendor/composer/autoload_classmap.php';

		foreach ($classmap as $class => $file) {
			if (!class_exists($class, false)) {
				require_once $file;
			}
			if (!class_exists($class, false)) continue;

			$rc = new \ReflectionClass($class);
			if ($rc->isAbstract()) continue;

			$interfaces = $rc->getInterfaceNames();

			$parts = explode("\\", $class);
			if (count($parts) < 2) continue;
			$app = $parts[1];

			$map =& $this->getMap();

			foreach ($interfaces as $interface) {
				$map[$app]["interface"][$interface][] = $class;
			}

			if (in_array(IBase::class, $interfaces) && is_callable([$class, 'getName'])) {
				try {
					$name = $class::getName();
					$map[$app]["name"][$name] = $class;
				} catch (\Throwable $e) {
					// ignore
				}
			}
		}
	}

	protected function getScanTargets(): array {
		return [];
	}
}

