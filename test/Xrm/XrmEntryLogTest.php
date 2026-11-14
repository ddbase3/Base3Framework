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

final class XrmEntryLogTest extends TestCase {

	public function testSupportsExistingObjectJsonAndArray(): void {
		$existing = new \Base3\CoreFoundation\Xrm\XrmEntryLog();
		$this->assertSame($existing, \Base3\CoreFoundation\Xrm\XrmEntryLog::unserialize($existing));
		$log = \Base3\CoreFoundation\Xrm\XrmEntryLog::unserialize('{"action":"created","user":3,"timestamp":"2026-01-02"}');
		$this->assertSame('created', $log->action);
		$this->assertSame(3, $log->user);
		$this->assertSame('2026-01-02', $log->timestamp);
		$this->assertSame('changed', \Base3\CoreFoundation\Xrm\XrmEntryLog::unserialize(['action'=>'changed'])->action);
	}

}
