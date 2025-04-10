<?php declare(strict_types=1);

namespace Base3\Api;

interface IContainer {

        const SHARED = 1;
        const NOOVERWRITE = 2;
	const ALIAS = 4;
	const PARAMETER = 8;

	public function getServiceList(): array;
	public function set(string $name, $classDefinition, $flags = 0): IContainer;
	public function has(string $name): bool;
	public function get(string $name);
}
