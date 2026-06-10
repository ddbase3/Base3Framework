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
use Base3\Api\ISchemaProvider;

/**
 * Interface IConfigValueModeResolver
 *
 * Resolves one specific kind of configuration value definition.
 *
 * Each implementation exposes one canonical mode through getMode(). The central
 * IConfigValueResolver uses these mode resolvers to transform raw configuration
 * definitions into final runtime values.
 *
 * Implementations may support internal legacy aliases in supports(), but aliases
 * are intentionally not part of this interface. Only getMode() is public and
 * should be shown in configuration UIs.
 *
 * The technical class name from IBase::getName() remains separate from the mode
 * name. For example, a resolver may return:
 * - getName(): fixedconfigvaluemoderesolver
 * - getMode(): fixed
 *
 * The schema returned by getSchema() describes only the mode-specific payload
 * fields. It must not include the common "mode" property, because the mode is
 * already provided by getMode() and handled by the central resolver or UI.
 */
interface IConfigValueModeResolver extends IBase, ISchemaProvider {

	/**
	 * Returns the canonical public mode name handled by this resolver.
	 *
	 * This value is used for new configuration definitions and for UI select
	 * options. It must not return legacy aliases or short technical class names.
	 *
	 * Examples:
	 * - fixed
	 * - configuration
	 * - env
	 * - file
	 *
	 * @return string Canonical public mode name
	 */
	public function getMode(): string;

	/**
	 * Checks whether this resolver can resolve the given configuration value.
	 *
	 * Implementations may support canonical mode definitions, legacy aliases, or
	 * convenience formats. For example, the fixed resolver may accept scalar values
	 * and arrays without a mode, while the configuration resolver may accept the
	 * legacy mode "config" in addition to the canonical mode "configuration".
	 *
	 * The central resolver uses this method to find the matching mode resolver.
	 * Implementations should be strict enough to avoid accidental matches with
	 * unrelated definitions.
	 *
	 * @param array|string|int|float|bool|null $config Raw value or resolver definition
	 * @return bool True if this resolver can resolve the given definition
	 */
	public function supports(array|string|int|float|bool|null $config): bool;

	/**
	 * Resolves the given configuration value definition.
	 *
	 * This method is called only after supports() returned true. Implementations
	 * should still validate all required fields and throw a RuntimeException or a
	 * more specific exception when the definition is incomplete or invalid.
	 *
	 * @param array|string|int|float|bool|null $config Raw value or resolver definition
	 * @return mixed Final resolved runtime value
	 */
	public function resolve(array|string|int|float|bool|null $config): mixed;

	/**
	 * Returns the schema for the mode-specific configuration payload.
	 *
	 * The returned schema must describe only the fields belonging to this mode.
	 * It must not include the common "mode" property. Generic configuration UIs
	 * should combine getMode() with this schema when rendering or storing values.
	 *
	 * Example for the "env" mode:
	 * [
	 * 	'type' => 'object',
	 * 	'properties' => [
	 * 		'name' => [
	 * 			'type' => 'string'
	 * 		]
	 * 	],
	 * 	'required' => ['name']
	 * ]
	 *
	 * @return array Schema fragment for the mode-specific payload without "mode"
	 */
	public function getSchema(): array;
}
