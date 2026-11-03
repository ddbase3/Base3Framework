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

require_once __DIR__ . '/DummyOutput.php';
