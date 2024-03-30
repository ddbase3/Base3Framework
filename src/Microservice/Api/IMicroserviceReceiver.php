<?php

namespace Microservice\Api;

interface IMicroserviceReceiver {

	public function ping();
	public function connect($services);

}
