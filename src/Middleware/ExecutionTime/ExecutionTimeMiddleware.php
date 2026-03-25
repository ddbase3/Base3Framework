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

namespace Base3\Middleware\ExecutionTime;

use Base3\Middleware\Api\IMiddleware;

class ExecutionTimeMiddleware implements IMiddleware {

	private $next;

        public function setNext($next) {
		$this->next = $next;
	}

	public function process(): string {
		$start = microtime(true);

		$output = $this->next->process();

		$end = microtime(true);
		$durationInMs = ($end-$start) * 1000;
		$output .= "<!-- execution time " . round($durationInMs)." ms -->\n";
		return $output;
        }

}

