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

namespace Base3\Test\Xrm\File;

use PHPUnit\Framework\TestCase;
use Base3\Test\Xrm\FilterTestXrm;

final class FileTagXrmFilterModuleTest extends TestCase {

	public function testTagConnectionAndNegationWithOffset(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\File\FileTagXrmFilterModule();
		$x->allocations['xxtanews']=['a','c'];
		$this->assertSame('filetagxrmfiltermodule',$m::getName());
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','news')));
		$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','news'),true));
		$this->assertSame('xxtanews',$x->lastAlloc);
		$this->assertSame(['entry:c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','news',1,1)));
		$this->assertSame(['b','d'],array_values($m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','notconn','news'),true)));
		$this->assertSame(['entry:b','entry:d'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','notconn','news')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','eq','news')));
	}

}

require_once __DIR__ . '/../FilterTestXrm.php';
