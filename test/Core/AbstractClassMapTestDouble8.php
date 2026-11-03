<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTestDouble8 extends AbstractClassMap {

			private string $basedir;

			public function __construct(IContainer $container, string $basedir) {
				parent::__construct($container);
				$this->basedir = $basedir;
				$this->classMapFile = DIR_TMP . 'classmap_' . uniqid('', true) . '.php';
				$this->ctorCacheFile = DIR_TMP . 'ctorcache_' . uniqid('', true) . '.php';
			}

			protected function getScanTargets(): array {
				return [['basedir' => $this->basedir, 'app' => 'ScanApp']];
			}

			public function scanOnly(): array {
				$classes = [];
				$this->scanClasses($classes, $this->basedir, 'ScanApp');
				return $classes;
			}
		}
