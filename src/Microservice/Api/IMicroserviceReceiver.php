<?php declare(strict_types=1);

namespace Base3\Microservice\Api;

interface IMicroserviceReceiver {

	public function ping();
	public function connect($services);

}
