<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Core\ComposerClassMap;
use PHPUnit\Framework\TestCase;

final class ComposerClassMapTestDouble2 extends ComposerClassMap {
			public function exposeTargets(): array {
				return $this->getScanTargets();
			}
		}
