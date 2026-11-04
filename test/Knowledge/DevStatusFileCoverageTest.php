<?php declare(strict_types=1);

namespace Base3\Test\Knowledge;

use Base3\Knowledge\DevStatusMicroservice;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class DevStatusFileCoverageTest extends TestCase {

	private function withLocalDirectory(callable $assertions): void {
		if (defined('DIR_LOCAL')) {
			$this->markTestSkipped('DIR_LOCAL belongs to the host bootstrap.');
		}
		$root = sys_get_temp_dir() . '/base3-devstatus-' . bin2hex(random_bytes(8));
		mkdir($root, 0700);
		define('DIR_LOCAL', $root . '/');
		try { $assertions($root); }
		finally {
			if (is_file($root . '/devstatus.json')) unlink($root . '/devstatus.json');
			rmdir($root);
		}
	}

	#[RunInSeparateProcess]
	public function testMissingDevelopmentStatusFileProducesEmptyKnowledgeSource(): void {
		$this->withLocalDirectory(function(): void {
			$service = new DevStatusMicroservice();
			$this->assertSame([], $service->getScopes());
			$this->assertNull($service->getFields('missing'));
			$this->assertNull($service->getData('missing'));
			$this->assertSame('devstatusmicroservice', $service::getName());
		});
	}

	#[RunInSeparateProcess]
	public function testStoredJsonScopesAreAvailableThroughExistingProjectionMethods(): void {
		$this->withLocalDirectory(function(string $root): void {
			$records = ['releases'=>[['id'=>1,'status'=>'ready','internal'=>'secret'], ['id'=>2,'status'=>'running','internal'=>'hidden']]];
			file_put_contents($root . '/devstatus.json', json_encode($records, JSON_THROW_ON_ERROR));
			$service = new DevStatusMicroservice();
			$this->assertSame(['releases'], $service->getScopes());
			$this->assertSame(['id','status','internal'], $service->getFields('releases'));
			$this->assertSame($records['releases'], $service->getData('releases'));
			$this->assertSame([['status'=>'ready'],['status'=>'running']], $service->getData('releases',['status']));
			$this->assertNull($service->getData('missing'));
		});
	}
}
