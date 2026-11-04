<?php declare(strict_types=1);

namespace Base3\Test\Migration\Exception;

use PHPUnit\Framework\TestCase;

final class MigrationExceptionTest extends TestCase {

	public function testMaintainsRuntimeExceptionSemantics(): void {
		$previous = new \RuntimeException('database');
		$exception = new \Base3\Migration\Exception\MigrationException('migration failed', 7, $previous);
		$this->assertInstanceOf(\RuntimeException::class, $exception);
		$this->assertSame('migration failed', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}

}
