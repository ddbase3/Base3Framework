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

namespace Base3\ConfigValue\Resolver;

use Base3\Api\IClassMap;
use Base3\ConfigValue\Api\IConfigValueModeResolver;
use Base3\ConfigValue\Api\IConfigValueResolver;
use RuntimeException;

/**
 * Resolves configuration value definitions using available mode resolvers.
 *
 * Mode resolvers are discovered through the BASE3 class map by looking for
 * implementations of IConfigValueModeResolver. This keeps the framework core
 * small while allowing plugins to provide additional modes.
 */
class ConfigValueResolver implements IConfigValueResolver {

	/**
	 * Cached list of discovered mode resolvers.
	 *
	 * @var IConfigValueModeResolver[]|null
	 */
	private ?array $modeResolvers = null;

	public function __construct(
		private readonly IClassMap $classMap
	) {}

	public static function getName(): string {
		return 'configvalueresolver';
	}

	public function resolve(array|string|int|float|bool|null $config): mixed {
		$matchingResolvers = [];

		foreach ($this->getModeResolvers() as $modeResolver) {
			if ($modeResolver->supports($config)) {
				$matchingResolvers[] = $modeResolver;
			}
		}

		if (count($matchingResolvers) === 0) {
			throw new RuntimeException($this->buildNoResolverMessage($config));
		}

		if (count($matchingResolvers) > 1) {
			throw new RuntimeException($this->buildMultipleResolversMessage($config, $matchingResolvers));
		}

		return $matchingResolvers[0]->resolve($config);
	}

	public function getModes(): array {
		$modes = [];

		foreach ($this->getModeResolvers() as $modeResolver) {
			$modes[] = $modeResolver->getMode();
		}

		$modes = array_values(array_unique($modes));
		sort($modes);

		return $modes;
	}

	public function getModeSchema(string $mode): ?array {
		$matchingResolvers = [];

		foreach ($this->getModeResolvers() as $modeResolver) {
			if ($modeResolver->getMode() === $mode) {
				$matchingResolvers[] = $modeResolver;
			}
		}

		if (count($matchingResolvers) === 0) {
			return null;
		}

		if (count($matchingResolvers) > 1) {
			throw new RuntimeException('Multiple config value mode resolvers expose mode: ' . $mode);
		}

		return $matchingResolvers[0]->getSchema();
	}

	public function getModeSchemas(): array {
		$schemas = [];

		foreach ($this->getModes() as $mode) {
			$schema = $this->getModeSchema($mode);

			if ($schema !== null) {
				$schemas[$mode] = $schema;
			}
		}

		return $schemas;
	}

	public function getModeResolverNames(): array {
		$names = [];

		foreach ($this->getModeResolvers() as $modeResolver) {
			$names[] = $modeResolver::getName();
		}

		$names = array_values(array_unique($names));
		sort($names);

		return $names;
	}

	/**
	 * Returns all available mode resolvers discovered through the class map.
	 *
	 * @return IConfigValueModeResolver[]
	 */
	protected function getModeResolvers(): array {
		if ($this->modeResolvers !== null) {
			return $this->modeResolvers;
		}

		$instances = $this->classMap->getInstancesByInterface(IConfigValueModeResolver::class);
		$this->modeResolvers = [];

		foreach ($instances as $instance) {
			if ($instance instanceof IConfigValueModeResolver) {
				$this->modeResolvers[] = $instance;
			}
		}

		return $this->modeResolvers;
	}

	protected function buildNoResolverMessage(array|string|int|float|bool|null $config): string {
		if (is_array($config) && array_key_exists('mode', $config)) {
			return 'No config value mode resolver found for mode: ' . (string) $config['mode'];
		}

		return 'No config value mode resolver found for the given value.';
	}

	/**
	 * @param IConfigValueModeResolver[] $matchingResolvers
	 */
	protected function buildMultipleResolversMessage(array|string|int|float|bool|null $config, array $matchingResolvers): string {
		$names = [];

		foreach ($matchingResolvers as $matchingResolver) {
			$names[] = $matchingResolver::getName();
		}

		if (is_array($config) && array_key_exists('mode', $config)) {
			return 'Multiple config value mode resolvers found for mode: ' . (string) $config['mode'] . ' (' . implode(', ', $names) . ')';
		}

		return 'Multiple config value mode resolvers found for the given value (' . implode(', ', $names) . ')';
	}
}
