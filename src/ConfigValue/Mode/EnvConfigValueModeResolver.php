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
use RuntimeException;

/**
 * Resolves configuration values from environment variables.
 *
 * The canonical mode is "env". The official field for the variable name is
 * "name". For backwards compatibility the field "value" is also accepted.
 */
class EnvConfigValueModeResolver implements IConfigValueModeResolver {

	private const MODE = 'env';

	public static function getName(): string {
		return 'envconfigvaluemoderesolver';
	}

	public function getMode(): string {
		return self::MODE;
	}

	public function supports(array|string|int|float|bool|null $config): bool {
		return is_array($config)
			&& ($config['mode'] ?? null) === self::MODE
			&& (isset($config['name']) || isset($config['value']));
	}

	public function resolve(array|string|int|float|bool|null $config): mixed {
		if (!is_array($config)) {
			throw new RuntimeException('Env config value definition must be an array.');
		}

		$name = $config['name'] ?? $config['value'] ?? null;

		if (!is_string($name) || $name === '') {
			throw new RuntimeException('Env config value definition requires a non-empty name.');
		}

		$value = getenv($name);

		if ($value === false) {
			return null;
		}

		return $value;
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'name' => [
					'type' => 'string',
					'description' => 'Environment variable name.'
				]
			],
			'required' => ['name']
		];
	}
}
