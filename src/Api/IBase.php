<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IBase
 *
 * Defines a base interface for all classes that provide a unique, namespaced identifier.
 */
interface IBase {

	/**
	 * Returns the technical name of the class, mostly the lower case version of the class name.
	 *
	 * This name must be globally unique, even across namespaces.
	 * It is typically used for registration, serialization, or lookup purposes.
	 *
	 * @return string Unique technical name of the class
	 */
	public static function getName(): string;
}
