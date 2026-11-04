<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use PHPUnit\Framework\TestCase;

final class AbstractMicroserviceConnectorTest extends TestCase {

	private function makeConnector(int $flags = 0): RecordingMicroserviceConnector {
		return new RecordingMicroserviceConnector('https://micro.example.test/endpoint.json', ['methods'=>[['name'=>'run','params'=>['left','right']]]],$flags);
	}

	public function testMapsArgumentsByMethodMetadataAndDecodesJson(): void {
		$c=$this->makeConnector();
		$this->assertSame('https://micro.example.test/endpoint.json',$c->getMicroserviceUrl());
		$this->assertSame($c,$c->getProxiedInstance());
		$this->assertSame(['answer'=>true],$c->run(7,9));
		$this->assertCount(1,$c->posts);
		$this->assertSame('run',$c->posts[0][1]['call']);
		$this->assertSame(['left'=>7,'right'=>9],json_decode($c->posts[0][1]['params'],true));
		$this->assertSame(0,$c->posts[0][1]['binarystream']);
	}

	public function testNullAndMalformedRemoteResponsesAreNotTrusted(): void {
		$c=$this->makeConnector();
		foreach(['',null,false,'broken'] as $reply) {
			$c->reply=$reply;
			$this->assertNull($c->run(1));
		}
	}

	public function testSerializedBooleanFalseIsNotMistakenForCorruptPayload(): void {
		$c=$this->makeConnector(\Base3\Microservice\Api\IMicroserviceFlags::SERIALIZED);
		$c->reply='b:0;';
		$this->assertFalse($c->run(1));
		$c->reply='not-serialized';
		$this->assertNull($c->run(1));
		$c->reply=serialize(['count'=>2]);
		$this->assertSame(['count'=>2],$c->run(1));
	}

	public function testBinaryStreamDoesNotDecodePayload(): void {
		$c=$this->makeConnector(\Base3\Microservice\Api\IMicroserviceFlags::BINARYSTREAM);
		$c->reply="\0raw-binary";
		$this->assertSame("\0raw-binary",$c->run('a'));
	}

}

require_once __DIR__ . '/RecordingMicroserviceConnector.php';
