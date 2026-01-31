<?php declare(strict_types=1);

namespace Base3\Worker;

use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Worker\Api\IJob;

class TestJob implements IOutput {

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IRequest $request
	) {}

	// Implementation of IBase

	public static function getName(): string {
		return "testjob";
	}

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {

		$jobName = $this->request->get('job');
		if ($jobName == null) {
			return "\nPlease select a job by using the &quot;job&quot; param.\n\n";
		}

		$job = $this->classmap->getInstanceByInterfaceName(IJob::class, $jobName);
		$res = $job->go();

		return "\n" . $res . "\n\n";
	}

	public function getHelp(): string {
		return 'Help of TestJob' . "\n";
	}
}
