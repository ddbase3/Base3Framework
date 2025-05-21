<?php declare(strict_types=1);

namespace Base3\Worker;

use Base3\Api\IOutput;
use Base3\Api\IRequest;
use Base3\Core\ServiceLocator;
use Base3\Worker\Api\IJob;

class TestJob implements IOutput {

	private $servicelocator;
	private $classmap;
	private $request;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		$this->request = $this->servicelocator->get(IRequest::class);
	}

	// Implementation of IBase

	public function getName() {
		return "testjob";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {

		$jobName = $this->request->get('job');
		if ($jobName == null) {
			return '<p>Bitte einen Job über den Parameter &quot;job&quot; angeben.</p>';
		}

		$job = $this->classmap->getInstanceByInterfaceName(IJob::class, $jobName);
		$res = $job->go();

		return '<p>' . $res . '</p>';
	}

	public function getHelp() {
		return 'Help of TestJob' . "\n";
	}
}
