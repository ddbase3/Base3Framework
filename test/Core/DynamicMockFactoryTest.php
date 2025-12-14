<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\DynamicMockFactory;
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
		if (!class_exists(\Base3Test\Core\NeedsIntString::class, false)) {
			eval('namespace Base3Test\Core; class NeedsIntString { public int $a; public string $b; public function __construct(int $a, string $b) { $this->a = $a; $this->b = $b; } }');
		}

		$obj = DynamicMockFactory::createMock(\Base3Test\Core\NeedsIntString::class);

		$this->assertInstanceOf(\Base3Test\Core\NeedsIntString::class, $obj);
		$this->assertSame(0, $obj->a);
		$this->assertIsString($obj->b);
	}

	public function testCreateMockForInterfaceGeneratesImplementationWithReturnTypes(): void {
		if (!interface_exists(\Base3Test\Core\ITestIface::class, false)) {
			eval('namespace Base3Test\Core; interface ITestIface { public function i(): int; public function s(): string; public function a(): array; public function v(): void; }');
		}

		$m = DynamicMockFactory::createMock(\Base3Test\Core\ITestIface::class);

		$this->assertInstanceOf(\Base3Test\Core\ITestIface::class, $m);
		$this->assertSame(0, $m->i());
		$this->assertIsString($m->s());
		$this->assertSame([], $m->a());
		$m->v();
		$this->assertTrue(true);
	}

	public function testCreateMockForAbstractClassImplementsAbstractMethods(): void {
		if (!class_exists(\Base3Test\Core\AbsWithMethod::class, false)) {
			eval('namespace Base3Test\Core; abstract class AbsWithMethod { abstract public function n(): int; public function ok(): string { return "ok"; } }');
		}

		$m = DynamicMockFactory::createMock(\Base3Test\Core\AbsWithMethod::class);

		$this->assertInstanceOf(\Base3Test\Core\AbsWithMethod::class, $m);
		$this->assertSame(0, $m->n());
		$this->assertSame('ok', $m->ok());
	}
}
