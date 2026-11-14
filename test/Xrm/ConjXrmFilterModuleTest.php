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

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class ConjXrmFilterModuleTest extends TestCase {

	public function testUnionIntersectionAndComplement(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\ConjXrmFilterModule();
		$a=new \Base3\CoreFoundation\Xrm\XrmFilter('ids','in',['a']);
		$b=new \Base3\CoreFoundation\Xrm\XrmFilter('ids','in',['b']);
		$this->assertSame('conjxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','or',[$a,$b])));
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','a')));
		$x->matchingIds=['a','c'];
		$this->assertSame(['a','c'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','or',[$a,$b]),true)));
		$this->assertSame(['a','c'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','and',[$a,$b]),true)));
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','not',$a),true)));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','unknown',[$a])));
	}

	public function testEmptyResultSetsAndListSlicing(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\ConjXrmFilterModule();
		$f=new \Base3\CoreFoundation\Xrm\XrmFilter('ids','in',['a']);
		$x->matchingIds=[];
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','or',[$f,$f]),true));
		$x->matchingIds=['a','b','c'];
		$this->assertSame(['entry:b'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('conj','or',[$f,$f],1,1)));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
