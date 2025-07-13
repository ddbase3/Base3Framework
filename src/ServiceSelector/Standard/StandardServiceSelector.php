<?php declare(strict_types=1);

namespace Base3\ServiceSelector\Standard;

use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Standard service selector for single-language applications.
 */
class StandardServiceSelector extends AbstractServiceSelector {

	private static ?self $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}
}
