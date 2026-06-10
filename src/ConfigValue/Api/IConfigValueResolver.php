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

namespace Base3\ConfigValue\Api;

use Base3\Api\IBase;

/**
 * Interface IConfigValueResolver
 *
 * Resolves configuration value definitions into final runtime values.
 *
 * This interface is intentionally limited to value resolution. It does not
 * load, persist, or modify configuration storage. Storage concerns remain in
 * Base3\Configuration\Api\IConfiguration.
 *
 * Implementations usually delegate the actual resolution work to all available
 * IConfigValueModeResolver instances. These mode resolvers can be provided by
 * the framework itself or by plugins and are typically discovered through the
 * BASE3 class map.
 *
 * Supported definitions may be explicit objects such as:
 * [
 * 	'mode' => 'fixed',
 * 	'value' => 'example'
 * ]
 *
 * or raw scalar/array values. Raw values are usually handled by the fixed mode
 * resolver and returned unchanged.
 */
interface IConfigValueResolver extends IBase {

	/**
	 * Resolves a configuration value definition into its effective runtime value.
	 *
	 * Implementations must inspect the given definition, find exactly one matching
	 * IConfigValueModeResolver and return the resolved value from that resolver.
	 * If no resolver or more than one resolver matches, implementations should
	 * throw a RuntimeException or a more specific exception.
	 *
	 * The method accepts arrays and scalar values because configuration files may
	 * contain both explicit resolver definitions and simple literal values.
	 *
	 * @param array|string|int|float|bool|null $config Raw value or resolver definition
	 * @return mixed Final resolved runtime value
	 */
	public function resolve(array|string|int|float|bool|null $config): mixed;

	/**
	 * Returns all canonical config value modes available in the current runtime.
	 *
	 * The returned values are intended for configuration UIs and documentation.
	 * They must contain only official modes exposed by getMode() on each resolver.
	 * Legacy aliases that a resolver may support internally must not be included.
	 *
	 * Example return value:
	 * [
	 * 	'configuration',
	 * 	'env',
	 * 	'file',
	 * 	'fixed'
	 * ]
	 *
	 * @return string[] Canonical mode names sorted alphabetically
	 */
	public function getModes(): array;

	/**
	 * Returns the schema for one canonical config value mode.
	 *
	 * The returned schema is the mode-specific payload schema from the matching
	 * IConfigValueModeResolver. It does not include the common "mode" property.
	 * Returns null when no resolver exists for the given canonical mode.
	 *
	 * Legacy aliases are intentionally not resolved here. This method is intended
	 * for configuration UIs and should only expose official canonical modes.
	 *
	 * @param string $mode Canonical mode name as returned by getModes()
	 * @return array|null Schema fragment for the mode-specific payload, or null
	 */
	public function getModeSchema(string $mode): ?array;

	/**
	 * Returns all available mode schemas indexed by canonical mode name.
	 *
	 * The returned schemas are intended for generic configuration UIs. The array
	 * key is the mode to store in the config definition, while the schema describes
	 * the additional fields that belong to that mode.
	 *
	 * Example return value:
	 * [
	 * 	'env' => [
	 * 		'type' => 'object',
	 * 		'properties' => [
	 * 			'name' => ['type' => 'string']
	 * 		],
	 * 		'required' => ['name']
	 * 	]
	 * ]
	 *
	 * @return array<string,array> Mode schemas indexed by canonical mode name
	 */
	public function getModeSchemas(): array;

	/**
	 * Returns the technical names of all available config value mode resolvers.
	 *
	 * The names are taken from IBase::getName() on each resolver class. These names
	 * are technical resolver identifiers and must not be confused with public mode
	 * names returned by getModes().
	 *
	 * This method is useful for diagnostics, debugging, and administrative views
	 * that need to show which concrete resolver classes are available.
	 *
	 * @return string[] Technical resolver names sorted alphabetically
	 */
	public function getModeResolverNames(): array;
}
