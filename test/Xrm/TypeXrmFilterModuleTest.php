<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class TypeXrmFilterModuleTest extends TestCase {

	public function testTypeFilterUsesAllocationAndCaseNormalization(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\TypeXrmFilterModule();
		$this->assertSame('typexrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('type','eq','NEWS')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('app','eq','NEWS')));
		$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('type','eq','NEWS',2,7),true));
		$this->assertSame('alloc',$x->lastFilter->attr);
		$this->assertSame('xxtynews',$x->lastFilter->val);
		$this->assertSame(2,$x->lastFilter->offset);
		$this->assertSame(7,$x->lastFilter->limit);
	}

	public function testNegatedTypeAndUnsupportedOperator(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\TypeXrmFilterModule();
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('type','neq','NEWS'),true)));
		$this->assertSame(['entry:b','entry:d'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('type','neq','NEWS')));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('type','unknown','NEWS')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
