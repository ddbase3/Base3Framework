<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class TagXrmFilterModuleTest extends TestCase {

	public function testMatchesTagButHasNoBuiltinQueryImplementation(): void {
		$m=new \Base3\Xrm\TagXrmFilterModule();$x=new FilterTestXrm();
		$this->assertSame('tagxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('tag','conn','a')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('type','conn','a')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('tag','conn','a'),true));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
