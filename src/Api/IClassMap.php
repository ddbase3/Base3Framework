<?php declare(strict_types=1);

namespace Base3\Api;

interface IClassMap {
	public function instantiate(string $class);
}
