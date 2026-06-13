<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\NullObject;
use PHPUnit\Framework\TestCase;

final class NullObjectTest extends TestCase {

	public function testCallDoesNothingWhenDebugIsOff(): void {
		putenv('DEBUG=0');

		$obj = new NullObject();

		ob_start();
		$obj->anything('a', 123);
		$out = ob_get_clean();

		$this->assertSame('', $out);
	}

	public function testCallEchoesMessageWhenDebugIsOn(): void {
		putenv('DEBUG=1');

		$obj = new NullObject();

		ob_start();
		$obj->whatever();
		$out = ob_get_clean();

		$this->assertSame('NullObject called.', $out);
	}
}
