<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\ClassMap;
use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class ClassMapTestDouble2 extends ClassMap {
			public function exposeTargets(): array {
				return $this->getScanTargets();
			}
		}
