<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use PHPUnit\Framework\TestCase;

final class AbstractMicroserviceTest extends TestCase {

	public function testDispatchesNamedArgumentsAsJson(): void {
		$old=$_REQUEST;
		try {
			$_REQUEST=['call'=>'sum','params'=>'{"left":7,"right":5}'];
			$s=new SampleMicroservice();
			$this->assertSame('samplemicroservice',$s::getName());
			$this->assertSame('12',$s->getOutput('json',true));
			$this->assertSame(['left'=>7,'right'=>5],$_REQUEST['params']);
			$this->assertStringContainsString('sum',$s->getHelp());
			$this->assertStringContainsString('left',$s->getHelp());
		} finally { $_REQUEST=$old; }
	}

	public function testSerializationAndBinaryModesUseExistingEndpointProtocol(): void {
		$old=$_REQUEST;
		try {
			$s=new SampleMicroservice();
			$_REQUEST=['call'=>'message','params'=>'{"text":"Alice"}','serialized'=>1];
			$this->assertSame(serialize('Hello Alice'),$s->getOutput('json'));
			$_REQUEST=['call'=>'message','params'=>'{"text":"Bob"}','binarystream'=>1];
			$this->assertSame('Hello Bob',$s->getOutput('json'));
		} finally { $_REQUEST=$old; }
	}

	public function testInvalidMethodIsRejectedByReflection(): void {
		$old=$_REQUEST;
		try {
			$_REQUEST=['call'=>'absent','params'=>'{}'];
			$this->expectException(\ReflectionException::class);
			(new SampleMicroservice())->getOutput('json');
		} finally { $_REQUEST=$old; }
	}

}

require_once __DIR__ . '/SampleMicroservice.php';
