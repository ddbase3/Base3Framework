<?php declare(strict_types=1);

namespace Base3\Test\Settings\Json;

use PHPUnit\Framework\TestCase;

final class JsonSettingsStoreTest extends TestCase {

	private function dataDir(): string {
		$path = sys_get_temp_dir().'/base3-settings-test-'.bin2hex(random_bytes(8));
		mkdir($path, 0700);
		return $path;
	}

	private function removeDir(string $path): void {
		@unlink($path.'/cnf/settings.json');
		@unlink($path.'/cnf/settings.json.tmp');
		@rmdir($path.'/cnf');
		@rmdir($path);
	}

	public function testPersistsGroupedSettingsAndReloadsThem(): void {
		$dir = $this->dataDir();
		try {
			$config = new \Base3\Configuration\ArrayConfiguration(['directories'=>['data'=>$dir]]);
			$store = new \Base3\Settings\Json\JsonSettingsStore($config);
			$this->assertFalse($store->has('feature', 'panel'));
			$this->assertSame(['default'], $store->get('feature', 'panel', ['default']));
			$store->set('feature', 'panel', ['active'=>true]);
			$store->set('feature', 'panel', ['active'=>true]);
			$this->assertTrue($store->has('feature', 'panel'));
			$this->assertSame(['panel'=>['active'=>true]], $store->getGroup('feature'));
			$store->save();
			$second = new \Base3\Settings\Json\JsonSettingsStore($config);
			$this->assertSame(['active'=>true], $second->get('feature','panel'));
			$second->remove('feature','panel');
			$second->save();
			$store->reload();
			$this->assertSame([], $store->getGroup('feature'));
		} finally { $this->removeDir($dir); }
	}

	public function testMissingDataPathIsRejected(): void {
		$this->expectException(\RuntimeException::class);
		new \Base3\Settings\Json\JsonSettingsStore(new \Base3\Configuration\ArrayConfiguration());
	}

	public function testEmptyDatasetKeysAreRejected(): void {
		$dir = $this->dataDir();
		try {
			$store = new \Base3\Settings\Json\JsonSettingsStore(new \Base3\Configuration\ArrayConfiguration(['directories'=>['data'=>$dir]]));
			$this->expectException(\RuntimeException::class);
			$store->set('group', '', []);
		} finally { $this->removeDir($dir); }
	}

	public function testCorruptJsonIsRejectedOnReload(): void {
		$dir = $this->dataDir();
		try {
			mkdir($dir.'/cnf');
			file_put_contents($dir.'/cnf/settings.json', '{invalid');
			$this->expectException(\RuntimeException::class);
			new \Base3\Settings\Json\JsonSettingsStore(new \Base3\Configuration\ArrayConfiguration(['directories'=>['data'=>$dir]]));
		} finally { $this->removeDir($dir); }
	}

	public function testInvalidNestedStructureIsRejected(): void {
		$dir = $this->dataDir();
		try {
			mkdir($dir.'/cnf');
			file_put_contents($dir.'/cnf/settings.json', '{"group":{"setting":"scalar"}}');
			$this->expectException(\RuntimeException::class);
			new \Base3\Settings\Json\JsonSettingsStore(new \Base3\Configuration\ArrayConfiguration(['directories'=>['data'=>$dir]]));
		} finally { $this->removeDir($dir); }
	}

}
