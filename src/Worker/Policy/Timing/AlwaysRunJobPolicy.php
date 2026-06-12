<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Timing;

use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class AlwaysRunJobPolicy extends AbstractJobExecutionPolicy {

	public static function getName(): string {
		return 'alwaysrunjobpolicy';
	}

	public function shouldRun(string $jobName) {
		return true;
	}
}
