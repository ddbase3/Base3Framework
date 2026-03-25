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
