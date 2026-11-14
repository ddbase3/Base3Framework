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

namespace Base3\Test\Worker;

use Base3\CoreFoundation\Worker\Api\IWorker;

final class MasterWorkerFixture implements IWorker {

	public int $statusChecks = 0;
	public int $jobQueries = 0;
	public int $executions = 0;

	public function __construct(public bool $active = false, public array $jobs = []) {}

	public static function getName(): string { return 'masterworkerfixture'; }
	public function isActive() { $this->statusChecks++; return $this->active; }
	public function getPriority() { return 1; }
	public function getJobs() { $this->jobQueries++; return $this->jobs; }
	public function doJob($job) { $this->executions++; return 'done'; }
}
