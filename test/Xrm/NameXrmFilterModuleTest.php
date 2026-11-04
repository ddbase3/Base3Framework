<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class NameXrmFilterModuleTest extends TestCase {

	public function testMatchesNameButHasNoBuiltinQueryImplementation(): void {
		$m=new \Base3\Xrm\NameXrmFilterModule();$x=new FilterTestXrm();
		$this->assertSame('namexrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('name','contains','a')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('tag','contains','a')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('name','contains','a')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
