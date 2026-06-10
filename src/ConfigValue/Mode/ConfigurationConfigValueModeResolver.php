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

namespace Base3\ConfigValue\Mode;

use Base3\ConfigValue\Api\IConfigValueModeResolver;
use Base3\Configuration\Api\IConfiguration;
use RuntimeException;

/**
 * Resolves configuration values from the global BASE3 configuration service.
 *
 * The canonical mode is "configuration". For backwards compatibility this
 * resolver also accepts the legacy mode "config" and the legacy field "section".
 *
 * New definitions should use:
 * [
 * 	'mode' => 'configuration',
 * 	'group' => 'openai',
 * 	'key' => 'apikey'
 * ]
 *
 * Existing definitions using mode "config" and field "section" remain valid.
 */
class ConfigurationConfigValueModeResolver implements IConfigValueModeResolver {

	private const MODE = 'configuration';

	private const LEGACY_MODE_CONFIG = 'config';

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public static function getName(): string {
		return 'configurationconfigvaluemoderesolver';
	}

	public function getMode(): string {
		return self::MODE;
	}

	public function supports(array|string|int|float|bool|null $config): bool {
		if (!is_array($config)) {
			return false;
		}

		$mode = $config['mode'] ?? null;

		if ($mode !== self::MODE && $mode !== self::LEGACY_MODE_CONFIG) {
			return false;
		}

		return (isset($config['group']) || isset($config['section']))
			&& isset($config['key']);
	}

	public function resolve(array|string|int|float|bool|null $config): mixed {
		if (!is_array($config)) {
			throw new RuntimeException('Configuration config value definition must be an array.');
		}

		$group = $config['group'] ?? $config['section'] ?? null;
		$key = $config['key'] ?? null;

		if (!is_string($group) || $group === '') {
			throw new RuntimeException('Configuration config value definition requires a non-empty group.');
		}

		if (!is_string($key) || $key === '') {
			throw new RuntimeException('Configuration config value definition requires a non-empty key.');
		}

		if (!$this->configuration->hasGroup($group)) {
			throw new RuntimeException("Configuration group '$group' not found.");
		}

		if (!$this->configuration->hasValue($group, $key)) {
			throw new RuntimeException("Configuration key '$key' not found in group '$group'.");
		}

		return $this->configuration->getValue($group, $key);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'group' => [
					'type' => 'string',
					'description' => 'Configuration group name.'
				],
				'key' => [
					'type' => 'string',
					'description' => 'Configuration key inside the selected group.'
				]
			],
			'required' => ['group', 'key']
		];
	}
}
