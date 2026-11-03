<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Core\PluginClassMap;
use PHPUnit\Framework\TestCase;

final class PluginClassMapTestDouble2 extends PluginClassMap {
			public function exposeTargets(): array {
				return $this->getScanTargets();
			}
		}
