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

use Base3\CoreFoundation\Worker\Api\IJob;

final class WorkerTestJob implements IJob {
	public int $runs = 0;
	public function __construct(private readonly bool $active=true,private readonly int $priority=2) {}
	public static function getName(): string { return 'workertestjob'; }
	public function isActive() { return $this->active; }
	public function getPriority() { return $this->priority; }
	public function go() { $this->runs++;return 'done'; }
}
