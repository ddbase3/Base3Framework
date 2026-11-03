<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Core\ComposerClassMap;
use PHPUnit\Framework\TestCase;

final class ComposerClassMapTestDouble1 implements IContainer  {
			public function getServiceList(): array { return []; }
			public function set(string $name, $classDefinition, $flags = 0): IContainer { return $this; }
			public function remove(string $name) {}
			public function has(string $name): bool { return false; }
			public function get(string $name) { return null; }
		}
