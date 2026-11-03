<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;
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
