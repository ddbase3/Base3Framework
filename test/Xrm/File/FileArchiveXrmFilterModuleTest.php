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

final class FileArchiveXrmFilterModuleTest extends TestCase {

	public function testRecognizesFileArchiveAndNeverGeneratesItsOwnEntries(): void {
		$x=new \Base3\Core\Xrm\File\FileArchiveXrmFilterModule();$f=new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',0);
		$this->assertSame('filearchivexrmfiltermodule',$x::getName());
		$this->assertSame(0,$x->match(new \stdClass(),$f));
		$this->assertSame([], $x->getEntries(new \stdClass(),$f,true));
	}

}
