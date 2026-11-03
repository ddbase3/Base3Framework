<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTestDouble3 extends AbstractClassMap {

			public int $scanCalls = 0;

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				$this->scanCalls++;
				return [];
			}

			public function writeDummy(): void {
				file_put_contents($this->classMapFile, "<?php return ['x' => 1];\n");
			}

			public function getFile(): string {
				return $this->classMapFile;
			}
		}
