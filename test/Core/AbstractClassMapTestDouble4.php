<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTestDouble4 extends AbstractClassMap {

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [];
			}

			protected function generateFromComposerClassMap(): void {
				$this->map = [
					'AppX' => [
						'name' => ['demo' => \Base3Test\Core\ClassMapDemo::class],
						'interface' => [],
					],
				];
			}

			public function exposeGetMap(): array {
				$map = &$this->getMap();
				return $map;
			}
		}
