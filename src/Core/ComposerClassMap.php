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

use Base3\Api\IBase;

class ComposerClassMap extends AbstractClassMap {

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
