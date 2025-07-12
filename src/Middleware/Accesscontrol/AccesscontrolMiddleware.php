<?php declare(strict_types=1);

namespace Base3\Middleware\Accesscontrol;

use Base3\Middleware\Api\IMiddleware;
use Base3\Accesscontrol\Api\IAccesscontrol;

class AccesscontrolMiddleware implements IMiddleware {

	private IMiddleware $next;

	public function __construct(
		private readonly IAccesscontrol $accesscontrol
	) {}

	public function setNext($next): void {
		$this->next = $next;
	}

	public function process(): string {
		$this->accesscontrol->authenticate();
		return $this->next->process();
	}
}

