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

final class TagXrmFilterModuleTest extends TestCase {

	public function testMatchesTagButHasNoBuiltinQueryImplementation(): void {
		$m=new \Base3\Core\Xrm\TagXrmFilterModule();$x=new FilterTestXrm();
		$this->assertSame('tagxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','a')));
		$this->assertSame(0,$m->match($x,new \Base3\CoreFoundation\Xrm\XrmFilter('type','conn','a')));
		$this->assertSame([], $m->getEntries($x,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','a'),true));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
