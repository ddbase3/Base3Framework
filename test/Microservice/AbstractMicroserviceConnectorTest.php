<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Microservice;

use PHPUnit\Framework\TestCase;

final class AbstractMicroserviceConnectorTest extends TestCase {

	private function makeConnector(int $flags = 0): RecordingMicroserviceConnector {
		return new RecordingMicroserviceConnector('https://micro.example.test/endpoint.json', ['methods'=>[['name'=>'run','params'=>['left','right']]]],new \Base3\Core\ServiceLocator(),$flags);
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
		$c=$this->makeConnector(\Base3\CoreFoundation\Microservice\Api\IMicroserviceFlags::SERIALIZED);
		$c->reply='b:0;';
		$this->assertFalse($c->run(1));
		$c->reply='not-serialized';
		$this->assertNull($c->run(1));
		$c->reply=serialize(['count'=>2]);
		$this->assertSame(['count'=>2],$c->run(1));
	}

	public function testBinaryStreamDoesNotDecodePayload(): void {
		$c=$this->makeConnector(\Base3\CoreFoundation\Microservice\Api\IMicroserviceFlags::BINARYSTREAM);
		$c->reply="\0raw-binary";
		$this->assertSame("\0raw-binary",$c->run('a'));
	}

}

require_once __DIR__ . '/RecordingMicroserviceConnector.php';
