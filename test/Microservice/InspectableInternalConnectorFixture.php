<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Microservice\Microservice\MicroserviceConnector;

final class InspectableInternalConnectorFixture extends MicroserviceConnector {
	public string|false|null $reply = '"node"';
	public array $posts = [];

	protected function httpPost($url, $data) {
		$this->posts[] = [$url, $data];
		return $this->reply;
	}
}
