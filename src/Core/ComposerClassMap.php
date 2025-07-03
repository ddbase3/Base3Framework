<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBase;
use Base3\Api\IContainer;

class ClassMapComposer extends AbstractClassMap {

	protected function generateFromComposerClassMap(): void {
		// Load Composer's autoload class map
		$classmap = require dirname(__DIR__, 3) . '/vendor/composer/autoload_classmap.php';

		foreach ($classmap as $class => $file) {
			if (!class_exists($class, false)) {
				require_once $file;
			}
			if (!class_exists($class, false)) continue;

			$rc = new \ReflectionClass($class);
			if ($rc->isAbstract()) continue;

			$interfaces = $rc->getInterfaceNames();

			// Determine app name from namespace component [1]
			$parts = explode("\\", $class);
			if (count($parts) < 2) continue;
			$app = $parts[1];

			foreach ($interfaces as $interface) {
				$this->map[$app]["interface"][$interface][] = $class;
			}

			// Register name if IBase is implemented
			if (in_array(IBase::class, $interfaces) && is_callable([$class, 'getName'])) {
				try {
					$name = $class::getName();
					$this->map[$app]["name"][$name] = $class;
				} catch (\Throwable $e) {
					// Ignore failures
				}
			}
		}
	}

	protected function getScanTargets(): array {
		// Not used in this class, but required by base class
		return [];
	}
}

