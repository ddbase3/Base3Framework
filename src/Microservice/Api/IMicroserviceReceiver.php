<?php declare(strict_types=1);

namespace Base3\Microservice\Api;

/**
 * Interface IMicroserviceReceiver
 *
 * Defines a receiver interface for handling incoming microservice connections.
 */
interface IMicroserviceReceiver {

	/**
	 * Simple ping method to verify the receiver is reachable.
	 *
	 * @return mixed Implementation-defined result (e.g. true, string, etc.)
	 */
	public function ping();

	/**
	 * Establishes a connection and registers the provided services.
	 *
	 * @param mixed $services Service definitions or references
	 * @return void
	 */
	public function connect($services);

}

