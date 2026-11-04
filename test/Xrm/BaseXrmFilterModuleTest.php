<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class BaseXrmFilterModuleTest extends TestCase {

 	public function testMatchAndBasicIds(): void {
		$x = new FilterTestXrm(); $m = new \Base3\Xrm\BaseXrmFilterModule();
		$this->assertSame('basexrmfiltermodule', $m::getName());
		$this->assertSame(1, $m->match($x, null));
		$this->assertSame(1, $m->match($x, new \Base3\Xrm\XrmFilter('ids','in',['a'])));
		$this->assertSame(0, $m->match($x, new \Base3\Xrm\XrmFilter('tag','conn','a')));
		$this->assertSame(['a','b','c','d'], $m->getEntries($x,null,true));
		$this->assertSame(['entry:a','entry:b','entry:c','entry:d'], $m->getEntries($x,null));
		$this->assertSame(['b','d'], array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('ids','notin',['a','c']),true)));
		$this->assertSame(['entry:b','entry:d'], $m->getEntries($x,new \Base3\Xrm\XrmFilter('ids','in',['b','d'])));
	}

	public function testAppAllocationAndComplement(): void {
		$x = new FilterTestXrm();$m=new \Base3\Xrm\BaseXrmFilterModule();
		$this->assertSame(['a','c'], $m->getEntries($x,new \Base3\Xrm\XrmFilter('app','eq','PORTAL'),true));
		$this->assertSame('alloc', $x->lastFilter->attr);
		$this->assertSame('with', $x->lastFilter->op);
		$this->assertSame('xxapportal', $x->lastFilter->val);
		$this->assertSame(['b','d'], array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('app','neq','PORTAL'),true)));
	}

	public function testXrmPositiveNegativeAndUnsupportedFilter(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\BaseXrmFilterModule();
		$this->assertSame(['a','c'], $m->getEntries($x,new \Base3\Xrm\XrmFilter('xrm','eq','one'),true));
		$this->assertSame(['entry:b','entry:d'], $m->getEntries($x,new \Base3\Xrm\XrmFilter('xrm','neq','one')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('ids','gt','a')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
