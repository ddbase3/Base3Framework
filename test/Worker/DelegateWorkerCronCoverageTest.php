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

use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Test\Support\StaticClassMapStub;
use Base3\Core\Worker\DelegateWorker;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DelegateWorkerCronCoverageTest extends TestCase {

	private function withCron(callable $run): void {
		$this->assertFalse(defined('DIR_LOCAL'));
		$path = sys_get_temp_dir() . '/base3-cron-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($path . '/Worker', 0700, true));
		define('DIR_LOCAL', $path . '/');
		try {
			$map = new StaticClassMapStub();
			$job = new RecordingCronWorkerJobFixture();
			$map->register($job::getName(), $job);
			$run(new DelegateWorker($map, new ArrayConfiguration()), $job,
				$path . '/Worker/recordingcronworkerjobfixture.txt');
		} finally {
			$file = $path . '/Worker/recordingcronworkerjobfixture.txt';
			if (is_file($file)) unlink($file);
			rmdir($path . '/Worker');
			rmdir($path);
		}
	}

	public function testFirstCronCallCreatesMarkerButDoesNotRunJob(): void {
		$this->withCron(function(DelegateWorker $worker, RecordingCronWorkerJobFixture $job, string $path): void {
			$this->assertNull($worker->doJob($job::getName()));
			$this->assertSame(0, $job->runs);
			$this->assertTrue(is_file($path));
			$this->assertSame(19, strlen(trim(file_get_contents($path))));
		});
	}

	public function testDueCronJobRunsAndAdvancesMarker(): void {
		$this->withCron(function(DelegateWorker $worker, RecordingCronWorkerJobFixture $job, string $path): void {
			file_put_contents($path, '2020-01-01 00:00:00');
			$this->assertSame('cron executed', $worker->doJob($job::getName()));
			$this->assertSame(1, $job->runs);
			$this->assertNotSame('2020-01-01 00:00:00', file_get_contents($path));
		});
	}

	public function testFutureCronMarkerPreventsExecution(): void {
		$this->withCron(function(DelegateWorker $worker, RecordingCronWorkerJobFixture $job, string $path): void {
			file_put_contents($path, '2099-01-01 00:00:00');
			$this->assertNull($worker->doJob($job::getName()));
			$this->assertSame(0, $job->runs);
			$this->assertSame('2099-01-01 00:00:00', file_get_contents($path));
		});
	}

	public function testScheduledMinuteHourDayMonthAreCalculatedFromLastExecution(): void {
		$this->withCron(function(DelegateWorker $worker, RecordingCronWorkerJobFixture $job, string $path): void {
			$job->timeCode = [0, 2, 1, 1, '*'];
			file_put_contents($path, '2024-12-31 23:59:50');
			$this->assertSame('cron executed', $worker->doJob($job::getName()));
			$this->assertSame(1, $job->runs);
		});
	}
}

require_once __DIR__ . '/RecordingCronWorkerJobFixture.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
