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

namespace Base3\Test\Migration\Exception;

use PHPUnit\Framework\TestCase;

final class MigrationExceptionTest extends TestCase {

	public function testMaintainsRuntimeExceptionSemantics(): void {
		$previous = new \RuntimeException('database');
		$exception = new \Base3\CoreFoundation\Migration\Exception\MigrationException('migration failed', 7, $previous);
		$this->assertInstanceOf(\RuntimeException::class, $exception);
		$this->assertSame('migration failed', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}

}
