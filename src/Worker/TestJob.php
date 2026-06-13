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
