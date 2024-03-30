<?php

namespace Middleware\ExecutionTime;

use Middleware\Api\IMiddleware;

class ExecutionTimeMiddleware implements IMiddleware {

	private $next;

        public function setNext($next) {
		$this->next = $next;
	}

	public function process() {
		$start = microtime(true);

		$output = $this->next->process();

		$end = microtime(true);
		$durationInMs = ($end-$start) * 1000;
		$output .= "<!-- execution time " . round($durationInMs)." ms -->\n";
		return $output;
        }

}

