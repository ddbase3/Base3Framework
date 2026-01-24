<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Api\IOutput;

/**
 * Class OutputStub
 *
 * Simple, DI-free output stub for unit tests.
 */
class OutputStub implements IOutput {

	private string $name;
	private $outputCallback;
	private $helpCallback;

	public function __construct(string $name = 'index', ?callable $outputCallback = null, ?callable $helpCallback = null) {
		$this->name = $name;
		$this->outputCallback = $outputCallback ?? function(string $out): string {
			return 'OUT:' . $out;
		};
		$this->helpCallback = $helpCallback ?? function(): string {
			return 'HELP';
		};
	}

	public static function getName(): string {
		// Default fallback; most tests should override via constructor name + ClassMapStub registration.
		return 'outputstub';
	}

	public function getOutput($out = "html") {
		$cb = $this->outputCallback;
		return $cb((string)$out);
	}

	public function getHelp() {
		$cb = $this->helpCallback;
		return $cb();
	}

	public function withName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getRegisteredName(): string {
		return $this->name;
	}
}
