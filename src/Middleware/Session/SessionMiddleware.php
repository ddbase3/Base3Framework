<?php declare(strict_types=1);

namespace Base3\Middleware\Session;

use Base3\Middleware\Api\IMiddleware;
use Base3\Session\Api\ISession;

class SessionMiddleware implements IMiddleware {

	private IMiddleware $next;
	private ISession $session;

	public function __construct(ISession $session) {
		$this->session = $session;
	}

	public function setNext($next): void {
		$this->next = $next;
	}

	public function process(): string {
		// Ensure session is started if needed
		$this->session->start();

		return $this->next->process();
	}
}

