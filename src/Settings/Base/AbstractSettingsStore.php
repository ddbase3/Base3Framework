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

namespace Base3\Settings\Base;

use Base3\Settings\Api\ISettingsStore;
use LogicException;

/**
 * Interface-complete base for named settings backends.
 *
 * The default view is empty and owns no buffered data. Writes and removals
 * fail explicitly instead of silently losing settings. Existing concrete
 * implementations override every method.
 */
abstract class AbstractSettingsStore implements ISettingsStore {

	/** @inheritDoc */
	public function get(string $group, string $name, array $default = []): array {
		return $default;
	}

	/**
	 * A real settings store must persist replacements in its own backend.
	 *
	 * @param string $group Dataset group.
	 * @param string $name Dataset name.
	 * @param array $settings Replacement settings.
	 * @return void
	 * @throws LogicException Always, unless a backend overrides the method.
	 */
	public function set(string $group, string $name, array $settings): void {
		throw new LogicException(static::class . ' does not implement set().');
	}

	/** @inheritDoc */
	public function has(string $group, string $name): bool {
		return false;
	}

	/**
	 * A backend must implement deletion instead of silently ignoring it.
	 *
	 * @param string $group Dataset group.
	 * @param string $name Dataset name.
	 * @return void
	 * @throws LogicException Always, unless a backend overrides the method.
	 */
	public function remove(string $group, string $name): void {
		throw new LogicException(static::class . ' does not implement remove().');
	}

	/** @inheritDoc */
	public function getGroup(string $group): array {
		return [];
	}

	/** @inheritDoc */
	public function save(): void {
		// This base does not buffer writes.
	}

	/** @inheritDoc */
	public function reload(): void {
		// This base has no storage to reload.
	}
}
