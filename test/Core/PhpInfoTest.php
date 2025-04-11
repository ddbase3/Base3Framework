<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Base3\Core\PhpInfo;

class PhpInfoTest extends TestCase {

	public function testGetName() {
		$phpInfo = new PhpInfo();
		$this->assertEquals('phpinfo', $phpInfo->getName());
	}

	public function testGetHelp() {
		$phpInfo = new PhpInfo();
		$this->assertContains('phpinfo', $phpInfo->getHelp());
	}

	public function testGetOutputWhenDebugIsTrue() {
		putenv('DEBUG=1');

		ob_start();
		$output = (new PhpInfo())->getOutput();
		$content = ob_get_clean();

		$this->assertNotEmpty($content);
		$this->assertContains('PHP Version', $content);
	}

	public function testGetOutputWhenDebugIsFalse() {
		putenv('DEBUG=0');

		$output = (new PhpInfo())->getOutput();
		$this->assertSame('', $output);
	}
}

