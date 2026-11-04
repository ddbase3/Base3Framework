<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

final class ReceiverRegistrationStub {
	public array $registered=[];
	public function set($services): bool { $this->registered=$services;return true; }
}
