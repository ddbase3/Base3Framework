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

final class TypeXrmFilterModuleTest extends TestCase {

	public function testTypeFilterUsesAllocationAndCaseNormalization(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\TypeXrmFilterModule();
		$this->assertSame('typexrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','eq','NEWS')));
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('app','eq','NEWS')));
		$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','eq','NEWS',2,7),true));
		$this->assertSame('alloc',$x->lastFilter->attr);
		$this->assertSame('xxtynews',$x->lastFilter->val);
		$this->assertSame(2,$x->lastFilter->offset);
		$this->assertSame(7,$x->lastFilter->limit);
	}

	public function testNegatedTypeAndUnsupportedOperator(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\TypeXrmFilterModule();
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','neq','NEWS'),true)));
		$this->assertSame(['entry:b','entry:d'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','neq','NEWS')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','unknown','NEWS')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
