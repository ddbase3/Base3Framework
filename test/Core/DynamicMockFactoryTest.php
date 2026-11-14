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

use Base3\CoreFoundation\Util\DynamicMockFactory;
use PHPUnit\Framework\TestCase;

final class DynamicMockFactoryTest extends TestCase {

	public function testCreateMockThrowsForUnknownTypeWithoutAutoloadNoise(): void {
		$bufStarted = false;

		try {
			ob_start();
			$bufStarted = true;

			DynamicMockFactory::createMock('\Does\Not\Exist');

			$this->fail('Expected InvalidArgumentException was not thrown.');
		} catch (\InvalidArgumentException $e) {
			$this->assertStringContainsString('does not exist', $e->getMessage());
		} finally {
			if ($bufStarted) {
				ob_end_clean(); // swallow autoloader output like "Autoload: ... NOT FOUND"
			}
		}
	}

	public function testCreateMockBuiltinMocks(): void {
		$tz = DynamicMockFactory::createMock(\DateTimeZone::class);
		$this->assertInstanceOf(\DateTimeZone::class, $tz);

		$dt = DynamicMockFactory::createMock(\DateTimeImmutable::class);
		$this->assertInstanceOf(\DateTimeImmutable::class, $dt);

		$xml = DynamicMockFactory::createMock(\SimpleXMLElement::class);
		$this->assertInstanceOf(\SimpleXMLElement::class, $xml);

		$doc = DynamicMockFactory::createMock(\DOMDocument::class);
		$this->assertInstanceOf(\DOMDocument::class, $doc);
		$this->assertSame('root', $doc->documentElement?->tagName);
	}

	public function testCreateMockThrowsForPdoBuiltinMock(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Cannot auto-mock PDO');
		DynamicMockFactory::createMock(\PDO::class);
	}

	public function testCreateMockForConcreteClassWithConstructorCreatesArgs(): void {

		$obj = DynamicMockFactory::createMock(\Base3\Test\Core\NeedsIntString::class);

		$this->assertInstanceOf(\Base3\Test\Core\NeedsIntString::class, $obj);
		$this->assertSame(0, $obj->a);
		$this->assertIsString($obj->b);
	}

	public function testCreateMockForInterfaceGeneratesImplementationWithReturnTypes(): void {

		$m = DynamicMockFactory::createMock(\Base3\Test\Core\ITestIface::class);

		$this->assertInstanceOf(\Base3\Test\Core\ITestIface::class, $m);
		$this->assertSame(0, $m->i());
		$this->assertIsString($m->s());
		$this->assertSame([], $m->a());
		$m->v();
		$this->assertTrue(true);
	}

	public function testCreateMockForAbstractClassImplementsAbstractMethods(): void {

		$m = DynamicMockFactory::createMock(\Base3\Test\Core\AbsWithMethod::class);

		$this->assertInstanceOf(\Base3\Test\Core\AbsWithMethod::class, $m);
		$this->assertSame(0, $m->n());
		$this->assertSame('ok', $m->ok());
	}
}

require_once __DIR__ . '/NeedsIntString.php';
require_once __DIR__ . '/ITestIface.php';
require_once __DIR__ . '/AbsWithMethod.php';
