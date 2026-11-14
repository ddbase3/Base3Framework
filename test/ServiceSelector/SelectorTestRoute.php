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

use Base3\CoreFoundation\Route\Api\IRoute;

final class SelectorTestRoute implements IRoute {
	public array $examinedPaths = [];
	public array $dispatched = [];
	public function __construct(private readonly string $expectedPath, private readonly string $content) {}
	public function match(string $path): ?array {
		$this->examinedPaths[] = $path;
		return $path === $this->expectedPath ? ['path'=>$path,'data'=>'de'] : null;
	}
	public function dispatch(array $match): string {
		$this->dispatched[] = $match;
		return $this->content;
	}
}
