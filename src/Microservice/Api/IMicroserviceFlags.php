<?php declare(strict_types=1);

namespace Base3\Microservice\Api;

/**
 * Interface IMicroserviceFlags
 *
 * Defines constants used to modify microservice behavior.
 */
interface IMicroserviceFlags {

	/**
	 * Indicates the microservice is for internal use only.
	 */
	const INTERNALONLY = 1;

	/**
	 * Indicates the response is a raw binary stream (e.g. file download).
	 */
	const BINARYSTREAM = 2;

	/**
	 * Indicates that input and output are serialized (e.g. PHP serialization).
	 */
	const SERIALIZED = 4;

}

