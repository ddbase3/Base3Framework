<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTestDouble5 extends AbstractClassMap {

			public int $generateCalls = 0;
			private string $basedir;

			public function __construct(IContainer $container, string $basedir) {
				parent::__construct($container);
				$this->basedir = $basedir;
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [['basedir' => $this->basedir]];
			}

			public function generate($regenerate = false): void {
				$this->generateCalls++;
				parent::generate($regenerate);
			}
		}
