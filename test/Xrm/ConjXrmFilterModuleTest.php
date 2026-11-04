<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class ConjXrmFilterModuleTest extends TestCase {

	public function testUnionIntersectionAndComplement(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\ConjXrmFilterModule();
		$a=new \Base3\Xrm\XrmFilter('ids','in',['a']);
		$b=new \Base3\Xrm\XrmFilter('ids','in',['b']);
		$this->assertSame('conjxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('conj','or',[$a,$b])));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('tag','conn','a')));
		$x->matchingIds=['a','c'];
		$this->assertSame(['a','c'],array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','or',[$a,$b]),true)));
		$this->assertSame(['a','c'],array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','and',[$a,$b]),true)));
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','not',$a),true)));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','unknown',[$a])));
	}

	public function testEmptyResultSetsAndListSlicing(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\ConjXrmFilterModule();
		$f=new \Base3\Xrm\XrmFilter('ids','in',['a']);
		$x->matchingIds=[];
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','or',[$f,$f]),true));
		$x->matchingIds=['a','b','c'];
		$this->assertSame(['entry:b'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('conj','or',[$f,$f],1,1)));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
