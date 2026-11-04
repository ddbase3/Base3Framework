<?php declare(strict_types=1);

namespace Base3\Test\Migration\No;

use PHPUnit\Framework\TestCase;

final class NoMigrationRunnerTest extends TestCase {

	public function testDefaultRunnerRequiresNoDatabase(): void {
		$runner = new \Base3\Migration\No\NoMigrationRunner();
		$this->assertNull($runner->migrate());
	}

}
