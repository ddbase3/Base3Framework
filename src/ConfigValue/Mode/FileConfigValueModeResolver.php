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
 * Resolves configuration values from files.
 *
 * The canonical mode is "file". The file path must be provided through the
 * "path" field. By default, the file content is returned as a trimmed string,
 * which is suitable for common runtime values such as API keys stored in files.
 */
class FileConfigValueModeResolver implements IConfigValueModeResolver {

	private const MODE = 'file';

	public static function getName(): string {
		return 'fileconfigvaluemoderesolver';
	}

	public function getMode(): string {
		return self::MODE;
	}

	public function supports(array|string|int|float|bool|null $config): bool {
		return is_array($config)
			&& ($config['mode'] ?? null) === self::MODE
			&& isset($config['path']);
	}

	public function resolve(array|string|int|float|bool|null $config): mixed {
		if (!is_array($config)) {
			throw new RuntimeException('File config value definition must be an array.');
		}

		$path = $config['path'] ?? null;

		if (!is_string($path) || $path === '') {
			throw new RuntimeException('File config value definition requires a non-empty path.');
		}

		if (!is_readable($path)) {
			throw new RuntimeException('Config value file is not readable: ' . $path);
		}

		$content = file_get_contents($path);

		if ($content === false) {
			throw new RuntimeException('Could not read config value file: ' . $path);
		}

		if ($this->shouldTrim($config['trim'] ?? true)) {
			return trim($content);
		}

		return $content;
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'path' => [
					'type' => 'string',
					'description' => 'Absolute path to the file containing the value.'
				],
				'trim' => [
					'type' => 'boolean',
					'description' => 'Trim leading and trailing whitespace from the file content.',
					'default' => true
				]
			],
			'required' => ['path']
		];
	}

	protected function shouldTrim(mixed $value): bool {
		if (is_bool($value)) {
			return $value;
		}

		if (is_int($value)) {
			return $value === 1;
		}

		$value = strtolower(trim((string) $value));

		return in_array($value, ['1', 'true', 'yes', 'on'], true);
	}
}
