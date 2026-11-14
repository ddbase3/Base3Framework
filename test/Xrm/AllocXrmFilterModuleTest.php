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

final class AllocXrmFilterModuleTest extends TestCase {

	public function testObjectArrayAndScalarAllocations(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\AllocXrmFilterModule();
		$this->assertSame('allocxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','with','group')));
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','eq','group')));
		foreach(['group', ['id'=>'group'],(object)['id'=>'group']] as $value) {
			$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','with',$value),true));
			$this->assertSame('group',$x->lastAlloc);
		}
		$this->assertSame(['entry:c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','with','group',1,1)));
	}

	public function testNegatedAllocationAndUnknownAllocation(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\AllocXrmFilterModule();
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','without','group'),true)));
		$this->assertSame(['entry:b','entry:d'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','without','group')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','with','nonexistent')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('alloc','eq','group')));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
