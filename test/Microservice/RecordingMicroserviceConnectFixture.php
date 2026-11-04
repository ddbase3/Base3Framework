<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

final class RecordingMicroserviceConnectFixture {

	public int $calls=0;
	public function connect(): void { $this->calls++; }
}
