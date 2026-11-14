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

use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IPlugin;
use Base3\Core\PluginClassMap;
use PHPUnit\Framework\TestCase;

final class PluginClassMapTestDouble3 extends PluginClassMap {

			private array $fakeMap;

			public function __construct(IContainer $container) {
				parent::__construct($container);
				$this->fakeMap = [
					'PluginA' => [
						'interface' => [
							IPlugin::class => ['PluginA\\MyPlugin'],
						],
					],
					'PluginB' => [
						'interface' => [
							'SomeOtherInterface' => ['PluginB\\X'],
						],
					],
					'PluginC' => [
						// missing interface key -> ignored
					],
				];
			}

			protected function getScanTargets(): array {
				return [];
			}

			protected function &getMap(): array {
				return $this->fakeMap;
			}
		}
