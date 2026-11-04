<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Worker\Api\IJob;

final class WorkerTestJob implements IJob {
	public int $runs = 0;
	public function __construct(private readonly bool $active=true,private readonly int $priority=2) {}
	public static function getName(): string { return 'workertestjob'; }
	public function isActive() { return $this->active; }
	public function getPriority() { return $this->priority; }
	public function go() { $this->runs++;return 'done'; }
}
