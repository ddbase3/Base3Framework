<?php declare(strict_types=1);

namespace Base3\Microservice\Api;

use Base3\Api\IConnector;
use Base3\Api\IProxy;

/**
 * Interface IMicroserviceConnector
 *
 * Provides the base URL or identifier of the connected microservice.
 */
interface IMicroserviceConnector extends IConnector, IProxy {

	/**
	 * Returns the base URL or connection identifier of the microservice.
	 *
	 * @return string|null URL or identifier, or null if not available
	 */
	public function getMicroserviceUrl();

}

