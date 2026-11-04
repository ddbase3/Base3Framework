<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Route\Api\IRoute;

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
