<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Middleware\Api\IMiddleware;

final class SelectorTestMiddleware implements IMiddleware {
	private ?IMiddleware $next = null;
	public int $calls = 0;
	public function __construct(private readonly string $label) {}
	public function setNext($next): void { $this->next = $next; }
	public function process(): string {
		$this->calls++;
		return $this->label.'['.$this->next->process().']';
	}
}
