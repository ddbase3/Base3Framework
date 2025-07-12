<?php declare(strict_types=1);

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

