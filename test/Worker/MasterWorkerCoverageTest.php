<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Test\Logger\RecordingLoggerFixture;
use Base3\Worker\MasterWorker;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class MasterWorkerCoverageTest extends TestCase {

	private function withLockDirectory(callable $assertions): void {
		if (defined('DIR_TMP')) {
			$this->markTestSkipped('The global temporary directory is owned by the host bootstrap.');
		}
		$root = sys_get_temp_dir() . '/base3-masterworker-' . bin2hex(random_bytes(8));
		mkdir($root, 0700);
		define('DIR_TMP', $root . '/');
		try { $assertions($root); }
		finally {
			if (file_exists($root . '/masterworker.lock')) unlink($root . '/masterworker.lock');
			rmdir($root);
		}
	}

	private function runCapturingOutput(MasterWorker $worker): array {
		ob_start();
		try { $result = $worker->getOutput(); }
		finally { $printed = ob_get_clean(); }
		return [$result, $printed];
	}

	#[RunInSeparateProcess]
	public function testInactiveWorkersAreSkippedWithoutInspectingTheirJobs(): void {
		$this->withLockDirectory(function(): void {
			$logger = new RecordingLoggerFixture();
			$delegate = new MasterWorkerFixture(false, [['name'=>'job','active'=>true,'priority'=>1]]);
			$master = new MasterWorker($logger, ['offline' => static fn() => $delegate]);
			$this->assertSame('masterworker', $master::getName());
			[$result,$output] = $this->runCapturingOutput($master);
			$this->assertStringContainsString('Runtime:', $result);
			$this->assertSame($result . "\n", $output);
			$this->assertSame(1, $delegate->statusChecks);
			$this->assertSame(0, $delegate->jobQueries);
			$this->assertSame(0, $delegate->executions);
			$this->assertCount(1, $logger->records);
			$this->assertSame(['scope'=>'masterworker'], $logger->records[0][2]);
		});
	}

	#[RunInSeparateProcess]
	public function testInactiveJobsAreNotQueued(): void {
		$this->withLockDirectory(function(): void {
			$logger = new RecordingLoggerFixture();
			$delegate = new MasterWorkerFixture(true, [['name'=>'disabled','active'=>false,'priority'=>4]]);
			$master = new MasterWorker($logger, ['active' => static fn() => $delegate]);
			[$result] = $this->runCapturingOutput($master);
			$this->assertStringContainsString('Runtime:', $result);
			$this->assertSame(1, $delegate->jobQueries);
			$this->assertSame(0, $delegate->executions);
			$this->assertCount(1, $logger->records);
		});
	}

	#[RunInSeparateProcess]
	public function testAlreadyHeldWorkerLockPreventsDuplicateExecution(): void {
		$this->withLockDirectory(function(string $root): void {
			$handle = fopen($root . '/masterworker.lock', 'c');
			$this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));
			try {
				$logger = new RecordingLoggerFixture();
				$delegate = new MasterWorkerFixture(true);
				$master = new MasterWorker($logger, ['active' => static fn() => $delegate]);
				[$result,$output] = $this->runCapturingOutput($master);
				$this->assertSame('MasterWorker skipped: lock is already held.', $result);
				$this->assertSame($result . "\n", $output);
				$this->assertSame(0, $delegate->statusChecks);
				$this->assertCount(1, $logger->records);
			} finally {
				flock($handle, LOCK_UN);
				fclose($handle);
			}
		});
	}

	#[RunInSeparateProcess]
	public function testWorkerProviderExceptionReleasesAcquiredLock(): void {
		$this->withLockDirectory(function(string $root): void {
			$master = new MasterWorker(new RecordingLoggerFixture(), [
				'failing' => static function(): never { throw new \RuntimeException('worker unavailable'); }
			]);
			ob_start();
			try {
				try { $master->getOutput(); $this->fail('Worker exception was swallowed.'); }
				catch (\RuntimeException $error) { $this->assertSame('worker unavailable', $error->getMessage()); }
			} finally { ob_end_clean(); }
			$handle = fopen($root . '/masterworker.lock', 'c');
			try { $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB)); }
			finally { flock($handle, LOCK_UN); fclose($handle); }
		});
	}
}

require_once __DIR__ . '/MasterWorkerFixture.php';
require_once __DIR__ . '/../Logger/RecordingLoggerFixture.php';
