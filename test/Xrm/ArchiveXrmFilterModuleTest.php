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

final class ArchiveXrmFilterModuleTest extends TestCase {

	public function testUndefinedArchiveAndSlicing(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\ArchiveXrmFilterModule();
		$this->assertSame('archivexrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq','undef')));
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('name','eq','x')));
		$this->assertSame(['a','b','c','d'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq','undef'),true));
		$this->assertSame(['entry:b','entry:c'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq','undef',1,2)));
		$this->assertSame(['entry:c','entry:d'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq','undef',2)));
		$this->assertSame(['entry:a'],$m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq','undef',null,1)));
	}

	public function testUnknownArchiveComparisons(): void {
		$x=new FilterTestXrm();$m=new \Base3\Core\Xrm\ArchiveXrmFilterModule();
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',1),true));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','neq','undef')));
		$this->assertSame(['a','b','c','d'], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','neq',1),true));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
