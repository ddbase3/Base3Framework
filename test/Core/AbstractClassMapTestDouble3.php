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

namespace Base3\Test\Core;

use Base3\CoreFoundation\Api\IBase;
use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Base\AbstractClassMap;
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
