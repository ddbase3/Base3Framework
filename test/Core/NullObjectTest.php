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

namespace Base3\Test\Core;

use Base3\Core\NullObject;
use PHPUnit\Framework\TestCase;

final class NullObjectTest extends TestCase {

	public function testCallDoesNothingWhenDebugIsOff(): void {
		putenv('DEBUG=0');

		$obj = new NullObject();

		ob_start();
		$obj->anything('a', 123);
		$out = ob_get_clean();

		$this->assertSame('', $out);
	}

	public function testCallEchoesMessageWhenDebugIsOn(): void {
		putenv('DEBUG=1');

		$obj = new NullObject();

		ob_start();
		$obj->whatever();
		$out = ob_get_clean();

		$this->assertSame('NullObject called.', $out);
	}
}
