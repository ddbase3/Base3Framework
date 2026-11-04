<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Api\IOutput;
use Base3\Api\IHelp;

final class SelectorTestOutput implements IOutput, IHelp {
	public array $calls = [];
	public static function getName(): string { return 'selector-test-output'; }
	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->calls[] = [$out, $final];
		return 'response:' . $out;
	}
	public function getHelp(): string { return 'selector help'; }
}
