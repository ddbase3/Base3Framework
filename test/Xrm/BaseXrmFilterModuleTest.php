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

final class BaseXrmFilterModuleTest extends TestCase {

 	public function testMatchAndBasicIds(): void {
		$x = new FilterTestXrm(); $m = new \Base3\Core\Xrm\BaseXrmFilterModule();
		$this->assertSame('basexrmfiltermodule', $m::getName());
		$this->assertSame(1, $m->match($x, null));
		$this->assertSame(1, $m->match($x, new \Base3\CoreFoundation\Xrm\XrmFilter('ids','in',['a'])));
		$this->assertSame(0, $m->match($x, new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','a')));
		$this->assertSame(['a','b','c','d'], $m->getEntries($x,null,true));
		$this->assertSame(['entry:a','entry:b','entry:c','entry:d'], $m->getEntries($x,null));
		$this->assertSame(['b','d'], array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('ids','notin',['a','c']),true)));
		$this->assertSame(['entry:b','entry:d'], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('ids','in',['b','d'])));
	}

	public function testAppAllocationAndComplement(): void {
		$x = new FilterTestXrm();$m=new \Base3\Core\Xrm\BaseXrmFilterModule();
		$this->assertSame(['a','c'], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('app','eq','PORTAL'),true));
		$this->assertSame('alloc', $x->lastFilter->attr);
		$this->assertSame('with', $x->lastFilter->op);
		$this->assertSame('xxapportal', $x->lastFilter->val);
		$this->assertSame(['b','d'], array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('app','neq','PORTAL'),true)));
	}

	public function testXrmPositiveNegativeAndUnsupportedFilter(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\BaseXrmFilterModule();
		$this->assertSame(['a','c'], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('xrm','eq','one'),true));
		$this->assertSame(['entry:b','entry:d'], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('xrm','neq','one')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('ids','gt','a')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
