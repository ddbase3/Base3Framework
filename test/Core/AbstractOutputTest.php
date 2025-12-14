<?php declare(strict_types=1);

namespace Base3\Core;

use PHPUnit\Framework\TestCase;

final class AbstractOutputTest extends TestCase {

	public function testGetNameUsesLowercasedClassBasename(): void {
		self::assertSame('dummyoutput', DummyOutput::getName());
	}

	public function testGetHelpUsesClassBasenameWithNewline(): void {
		$out = new DummyOutput();

		self::assertSame("Help of DummyOutput\n", $out->getHelp());
	}
}

final class DummyOutput extends AbstractOutput {

	/**
	 * Signature must match Base3\Api\IOutput::getOutput($out = "html")
	 *
	 * @param mixed $out
	 * @return mixed
	 */
	public function getOutput($out = 'html') {
		return 'ok:' . (string)$out;
	}
}
