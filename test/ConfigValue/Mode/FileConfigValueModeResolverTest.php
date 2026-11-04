<?php declare(strict_types=1);

namespace Base3\Test\ConfigValue\Mode;

use PHPUnit\Framework\TestCase;

final class FileConfigValueModeResolverTest extends TestCase {

	public function testTrimModesAndSchema(): void {
		$r = new \Base3\ConfigValue\Mode\FileConfigValueModeResolver();
		$file = tempnam(sys_get_temp_dir(), 'base3-value-');
		try {
			file_put_contents($file, "  secret \n");
			$this->assertTrue($r->supports(['mode' => 'file', 'path' => $file]));
			$this->assertSame('secret', $r->resolve(['path' => $file]));
			$this->assertSame("  secret \n", $r->resolve(['path' => $file, 'trim' => false]));
			$this->assertSame("  secret \n", $r->resolve(['path' => $file, 'trim' => 'no']));
			$this->assertSame('secret', $r->resolve(['path' => $file, 'trim' => 'on']));
			$this->assertSame('secret', $r->resolve(['path' => $file, 'trim' => 1]));
			$this->assertSame(['path'], $r->getSchema()['required']);
		} finally {
			unlink($file);
		}
	}

	public function testRejectsUnreadableFile(): void {
		$r = new \Base3\ConfigValue\Mode\FileConfigValueModeResolver();
		$this->assertFalse($r->supports(['mode' => 'env', 'path' => '/tmp/secret']));
		$this->expectException(\RuntimeException::class);
		$r->resolve(['path' => sys_get_temp_dir().'/base3-file-that-does-not-exist-'.getmypid()]);
	}

	public function testRejectsEmptyPath(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\ConfigValue\Mode\FileConfigValueModeResolver())->resolve(['path' => '']);
	}

	public function testRejectsNonArrayDefinition(): void {
		$this->expectException(\RuntimeException::class);
		(new \Base3\ConfigValue\Mode\FileConfigValueModeResolver())->resolve(null);
	}

}
