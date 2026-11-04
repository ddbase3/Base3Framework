<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Worker\Api\ICron;

final class RecordingCronWorkerJobFixture implements ICron {
	public int $runs = 0;
	public array $timeCode = [null, null, null, null, null];

	public static function getName(): string { return 'recordingcronworkerjobfixture'; }
	public function isActive() { return true; }
	public function getPriority() { return 1; }
	public function getTimeCode() { return $this->timeCode; }
	public function go() { $this->runs++; return 'cron executed'; }
}
