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
 * Interface ISchemaProvider
 *
 * Provides a schema definition used for validating or describing structured data.
 */
interface ISchemaProvider {

	/**
	 * Returns the schema definition.
	 *
	 * The schema can be used for validation, documentation, or code generation purposes.
	 *
	 * @return array Associative array describing the schema structure
	 */
	public function getSchema(): array;

}

