<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Api\IModuleRegistry;
use RuntimeException;

/** Isolated registry exposing only the fixture's explicitly installed modules. */
final class DiagnosisTestModuleRegistry implements IModuleRegistry {

	/** @param array<string,string> $paths */
	public function __construct(private readonly array $paths = []) {}

	public function getModuleNames(): array {
		return array_keys($this->paths);
	}

	public function getModulePath(string $name): ?string {
		return $this->paths[$name] ?? null;
	}

	public function requireModulePath(string $name): string {
		return $this->paths[$name] ?? throw new RuntimeException('Unknown module: ' . $name);
	}

}
