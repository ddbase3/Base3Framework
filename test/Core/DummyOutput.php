<?php declare(strict_types=1);

namespace Base3\Core;

use PHPUnit\Framework\TestCase;

final class DummyOutput extends AbstractOutput {

	/**
	 * Signature must match Base3\Api\IOutput::getOutput(string $out = 'html', bool $final = false): string
	 *
	 * @param mixed $out
	 * @return mixed
	 */
	public function getOutput(string $out = 'html', bool $final = false): string {
		return 'ok:' . $out;
	}
}
