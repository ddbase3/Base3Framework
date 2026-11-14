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
