<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use Base3\Core\ServiceLocator;
use Base3\Xrm\XrmSearchService;
use PHPUnit\Framework\TestCase;

final class XrmSearchServiceTest extends TestCase {

	private function createService(XrmSearchFixture $local, XrmSearchFixture $global): XrmSearchService {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$container->set('xrm',$local,ServiceLocator::SHARED);
		$container->set('xrmglobal',$global,ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new XrmSearchService(); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testIdSearchCallsLocalXrmWithParsedFilter(): void {
		$before=$_REQUEST;
		try {
			$local=new XrmSearchFixture();$global=new XrmSearchFixture();
			$service=$this->createService($local,$global);
			$_REQUEST=['q'=>'{"attr":"type","op":"eq","val":"page"}','fo'=>'ids'];
			$this->assertSame('["one","two"]',$service->getOutput('json'));
			$this->assertSame('type',$local->calls[0][1]->attr);
			$this->assertSame(1,$local->calls[0][2]);
			$this->assertSame([],$global->calls);
			$this->assertSame('xrmsearchservice',$service::getName());
		} finally { $_REQUEST=$before; }
	}

	public function testGlobalDataSearchUsesGlobalXrm(): void {
		$before=$_REQUEST;
		try {
			$local=new XrmSearchFixture();$global=new XrmSearchFixture();
			$service=$this->createService($local,$global);
			$_REQUEST=['q'=>'{"attr":"name","op":"eq","val":"Hello"}','x'=>'global','fo'=>'data'];
			$this->assertSame('[{"id":"one","name":"Entry"}]',$service->getOutput('json'));
			$this->assertSame('name',$global->calls[0][1]->attr);
			$this->assertSame([],$local->calls);
			$this->assertStringContainsString('XrmSearchService',$service->getHelp());
		} finally { $_REQUEST=$before; }
	}

	public function testInvalidQueryIsRejectedBeforeBackendSearch(): void {
		$before=$_REQUEST;
		try {
			$local=new XrmSearchFixture();$global=new XrmSearchFixture();
			$service=$this->createService($local,$global);
			$_REQUEST=['q'=>'{"op":"eq"}','fo'=>'ids'];
			try { $service->getOutput('json'); $this->fail('Missing filter attribute was accepted.'); }
			catch (\InvalidArgumentException $error) { $this->assertStringContainsString('missing "attr"',$error->getMessage()); }
			$this->assertSame([],$local->calls);
		} finally { $_REQUEST=$before; }
	}
}

require_once __DIR__ . '/XrmSearchFixture.php';
