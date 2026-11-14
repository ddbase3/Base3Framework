<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Worker;

use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Test\Logger\RecordingLoggerFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AbstractSyncJobLifecycleCoverageTest extends TestCase {

	private function withJob(callable $run): void {
		$this->assertFalse(defined('DIR_LOCAL'));
		$path = sys_get_temp_dir() . '/base3-syncjob-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($path . '/Sync', 0700, true));
		define('DIR_LOCAL', $path . '/');
		$previous = ServiceLocator::getInstance();
		try {
			$logger = new RecordingLoggerFixture();
			$container = new ServiceLocator();
			$container->set('logger', $logger, IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$run(new TestSyncJobFixture($container), $logger);
		} finally {
			ServiceLocator::useInstance($previous);
			$file = $path . '/Sync/testsyncjobfixture.txt';
			if (is_file($file)) unlink($file);
			rmdir($path . '/Sync');
			rmdir($path);
		}
	}

	private function runJob(TestSyncJobFixture $job): string {
		ob_start();
		try { $job->go(); }
		finally { $output = ob_get_clean(); }
		return $output;
	}

	public function testIdenticalEntriesNeedNoWorkButStillAdvanceSyncMarker(): void {
		$this->withJob(function(TestSyncJobFixture $job, RecordingLoggerFixture $logger): void {
			$job->appEntries = ['same' => '2025-01-01 00:00:00'];
			$job->xrmEntries = ['same' => '2025-01-01 00:00:00'];
			$this->assertSame('testsyncjobfixture', $job::getName());
			$this->assertTrue($job->isActive());
			$this->assertSame(1, $job->getPriority());
			$this->assertSame('', $this->runJob($job));
			$this->assertSame([], $job->operations);
			$this->assertTrue(is_file($job->syncFile()));
			$this->assertStringContainsString('Nothing to do', $logger->records[1][1]);
		});
	}

	public function testNewAndUpdatedEntriesRunAllThreePhasesInCorrectDirection(): void {
		$this->withJob(function(TestSyncJobFixture $job, RecordingLoggerFixture $logger): void {
			$job->appEntries = [
				'fromApp' => '2025-01-02 00:00:00',
				'fromXrm' => '2025-01-01 00:00:00',
				'newApp' => '2025-01-03 00:00:00'
			];
			$job->xrmEntries = [
				'fromApp' => '2025-01-01 00:00:00',
				'fromXrm' => '2025-01-02 00:00:00',
				'newXrm' => '2025-01-03 00:00:00'
			];
			$output = $this->runJob($job);
			$this->assertCount(12, $job->operations);
			$this->assertSame(['createAppToXrmDisconnect', 'newApp'], $job->operations[0]);
			$this->assertSame(['createXrmToAppDisconnect', 'newXrm'], $job->operations[3]);
			$this->assertSame(['updateAppToXrmDisconnect', 'fromApp'], $job->operations[6]);
			$this->assertSame(['updateXrmToAppDisconnect', 'fromXrm'], $job->operations[9]);
			$this->assertStringContainsString('updateXrmToAppExecute: 1 / 1', $output);
			$this->assertTrue(is_file($job->syncFile()));
		});
	}

	public function testOldSingleSideEntriesAreDeletedAfterSyncTime(): void {
		$this->withJob(function(TestSyncJobFixture $job): void {
			file_put_contents($job->syncFile(), '2099-01-01 00:00:00');
			$job->appEntries = ['app' => '2025-01-01 00:00:00'];
			$job->xrmEntries = ['xrm' => '2025-01-01 00:00:00'];
			$this->runJob($job);
			$this->assertCount(6, $job->operations);
			$this->assertSame(['deleteFromAppDisconnect', 'app'], $job->operations[0]);
			$this->assertSame(['deleteFromAppExecute', 'app'], $job->operations[1]);
			$this->assertSame(['deleteFromXrmConnect', 'xrm'], $job->operations[5]);
		});
	}

	public function testFailedSyncDoesNotOverwriteLastSuccessfulMarker(): void {
		$this->withJob(function(TestSyncJobFixture $job): void {
			file_put_contents($job->syncFile(), '2099-01-01 00:00:00');
			$job->appEntries = ['same' => '2025-01-01 00:00:00'];
			$job->xrmEntries = ['same' => '2025-01-01 00:00:00'];
			$job->failCurrentSync();
			$this->assertStringContainsString('Sync failed!', $this->runJob($job));
			$this->assertSame('2099-01-01 00:00:00', file_get_contents($job->syncFile()));
		});
	}
}

require_once __DIR__ . '/TestSyncJobFixture.php';
require_once __DIR__ . '/../Logger/RecordingLoggerFixture.php';
