<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Timing;

use PHPUnit\Framework\TestCase;

final class AlwaysRunJobPolicyTest extends TestCase {

	public function testAlwaysAllowsExecutionWithoutConfiguration(): void {
		$p = new \Base3\Worker\Policy\Timing\AlwaysRunJobPolicy();
		$this->assertSame('alwaysrunjobpolicy', $p::getName());
		$this->assertTrue($p->shouldRun('cleanup'));
		$p->setData(['unexpected' => true]);
		$p->markRun('cleanup');
		$this->assertTrue($p->shouldRun('cleanup'));
		$this->assertSame('', $p->getReason());
		$this->assertSame([], $p->getSchema());
	}

}
