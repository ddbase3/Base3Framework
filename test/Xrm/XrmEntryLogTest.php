<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class XrmEntryLogTest extends TestCase {

	public function testSupportsExistingObjectJsonAndArray(): void {
		$existing = new \Base3\Xrm\XrmEntryLog();
		$this->assertSame($existing, \Base3\Xrm\XrmEntryLog::unserialize($existing));
		$log = \Base3\Xrm\XrmEntryLog::unserialize('{"action":"created","user":3,"timestamp":"2026-01-02"}');
		$this->assertSame('created', $log->action);
		$this->assertSame(3, $log->user);
		$this->assertSame('2026-01-02', $log->timestamp);
		$this->assertSame('changed', \Base3\Xrm\XrmEntryLog::unserialize(['action'=>'changed'])->action);
	}

}
