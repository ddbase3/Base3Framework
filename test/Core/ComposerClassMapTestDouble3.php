<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Core\ComposerClassMap;
use PHPUnit\Framework\TestCase;

final class ComposerClassMapTestDouble3 extends ComposerClassMap {

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->classMapFile = DIR_TMP . 'composerclassmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'composerctorcache_' . uniqid('', true) . '.php';
			}

			public function exposeMap(): array {
				$map = &$this->getMap();
				return $map;
			}

			public function getFile(): string {
				return $this->classMapFile;
			}
		}
