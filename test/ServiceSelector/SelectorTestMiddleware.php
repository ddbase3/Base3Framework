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

namespace Base3\Test\ServiceSelector;

use Base3\CoreFoundation\Middleware\Api\IMiddleware;

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
