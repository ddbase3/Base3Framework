<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Microservice\AbstractMicroserviceConnector;

final class RecordingMicroserviceConnector extends AbstractMicroserviceConnector {
	public array $posts = [];
	public string|false|null $reply = '{"answer":true}';
	protected function httpPost($url,$data) {
		$this->posts[]=[$url,$data];
		return $this->reply;
	}
}
