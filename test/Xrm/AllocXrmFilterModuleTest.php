<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class AllocXrmFilterModuleTest extends TestCase {

	public function testObjectArrayAndScalarAllocations(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\AllocXrmFilterModule();
		$this->assertSame('allocxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('alloc','with','group')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('type','eq','group')));
		foreach(['group', ['id'=>'group'],(object)['id'=>'group']] as $value) {
			$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','with',$value),true));
			$this->assertSame('group',$x->lastAlloc);
		}
		$this->assertSame(['entry:c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','with','group',1,1)));
	}

	public function testNegatedAllocationAndUnknownAllocation(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\AllocXrmFilterModule();
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','without','group'),true)));
		$this->assertSame(['entry:b','entry:d'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','without','group')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','with','nonexistent')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('alloc','eq','group')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
