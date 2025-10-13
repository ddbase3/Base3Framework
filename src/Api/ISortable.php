<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface ISortable
 *
 * Defines an optional priority-based sorting contract for objects.
 * Classes implementing this interface can provide an integer priority value
 * that allows consistent ordering when multiple implementations are collected,
 * e.g. in plugin or extension systems.
 */
interface ISortable {

	/**
	 * Returns the sort priority of this object.
	 *
	 * Higher values indicate later execution (lower priority values are executed first).
	 * Default implementations should typically return 0.
	 *
	 * @return int Sort priority
	 */
	public function getPriority(): int;
}

