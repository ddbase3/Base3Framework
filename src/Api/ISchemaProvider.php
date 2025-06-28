<?php declare(strict_types=1);

namespace Base3\Api;

interface ISchemaProvider {

	/* offer a schema for validating data */
	public function getSchema(): array;

}
