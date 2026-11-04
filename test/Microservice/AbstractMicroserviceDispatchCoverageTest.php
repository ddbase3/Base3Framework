<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use PHPUnit\Framework\TestCase;

final class AbstractMicroserviceDispatchCoverageTest extends TestCase {

	private function withRequest(array $request, callable $run): void {
		$previous = $_REQUEST;
		try { $_REQUEST = $request; $run(new SampleMicroservice()); }
		finally { $_REQUEST = $previous; }
	}

	public function testMissingArgumentsArePassedAsNull(): void {
		$this->withRequest(['call' => 'sum', 'params' => '{"left":7}'], function(SampleMicroservice $service): void {
			$this->assertSame('7', $service->getOutput('json'));
		});
	}

	public function testUnexpectedArgumentKeysDoNotChangePositionalBinding(): void {
		$this->withRequest(['call' => 'sum', 'params' => '{"right":9,"extra":100,"left":1}'], function(SampleMicroservice $service): void {
			$this->assertSame('10', $service->getOutput('json'));
		});
	}

	public function testBinaryStreamTakesPrecedenceOverSerializedResponse(): void {
		$this->withRequest(['call' => 'message', 'params' => '{"text":"world"}', 'binarystream' => 1, 'serialized' => 1], function(SampleMicroservice $service): void {
			$this->assertSame('Hello world', $service->getOutput('json'));
		});
	}

	public function testFalseFlagsUseJsonEncoding(): void {
		$this->withRequest(['call' => 'sum', 'params' => '{"left":4,"right":5}', 'binarystream' => 0, 'serialized' => 0], function(SampleMicroservice $service): void {
			$this->assertSame('9', $service->getOutput('json'));
		});
	}

	public function testEmptyParameterObjectStillCallsMethod(): void {
		$this->withRequest(['call' => 'sum', 'params' => '{}'], function(SampleMicroservice $service): void {
			$this->assertSame('0', $service->getOutput('json'));
		});
	}
}

require_once __DIR__ . '/SampleMicroservice.php';
