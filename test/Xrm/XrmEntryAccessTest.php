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

final class XrmEntryAccessTest extends TestCase {

	public function testDecodesJsonObjectAndExistingInstance(): void {
		$existing = new \Base3\CoreFoundation\Xrm\XrmEntryAccess();
		$this->assertSame($existing, \Base3\CoreFoundation\Xrm\XrmEntryAccess::unserialize($existing));
		$access = \Base3\CoreFoundation\Xrm\XrmEntryAccess::unserialize('{"mode":"read","usergroup":"group","id":5}');
		$this->assertSame('read', $access->mode);
		$this->assertSame('group', $access->usergroup);
		$this->assertSame(5, $access->id);
		$object = \Base3\CoreFoundation\Xrm\XrmEntryAccess::unserialize((object)['mode' => 'write', 'usergroup' => 'user', 'id' => 'abc']);
		$this->assertSame('abc', $object->id);
	}

}
