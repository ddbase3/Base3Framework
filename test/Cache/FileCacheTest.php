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

namespace Base3\Test\Cache;

use Base3\Core\Cache\FileCache;
use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase {

	private function createCache(RecordingDatabaseStub $db): FileCache {
		$previous = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$container->set('database', $db, ServiceLocator::SHARED);
		$container->set('configuration', new ArrayConfiguration(['base'=>['url'=>'https://files.example/']]), ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new FileCache(); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testCachedResponseRenewsExpiryWithoutDownloadingFile(): void {
		$db = new RecordingDatabaseStub();
		$db->singleRow = ['value'=>'abcde','extension'=>'png'];
		$cache = $this->createCache($db);
		$url = 'https://source.example/a.png';
		$this->assertSame([$url=>'https://files.example/userfiles/cache/ab/cd/abcde.png'], $cache->getCacheUrl($url));
		$this->assertSame(1, $db->connectCalls);
		$this->assertStringContainsString(sha1($url), $db->singleQueries[0]);
		$this->assertStringContainsString('UPDATE `cache` SET `timeout`', $db->nonQueries[0]);
		$this->assertCount(1, $db->nonQueries);
	}

	public function testBatchPreservesOriginalUrlsAsKeys(): void {
		$db = new RecordingDatabaseStub();
		$db->singleRow = ['value'=>'abcde','extension'=>'jpg'];
		$cache = $this->createCache($db);
		$urls = ['https://source/a.jpg','https://source/b.jpg'];
		$result = $cache->getCacheUrls($urls);
		$this->assertSame($urls, array_keys($result));
		$this->assertCount(2, $db->singleQueries);
		$this->assertCount(2, $db->nonQueries);
	}

	public function testEmptyBatchDoesNotQueryOrWriteFiles(): void {
		$db = new RecordingDatabaseStub();
		$cache = $this->createCache($db);
		$this->assertSame([], $cache->getCacheUrls([]));
		$this->assertSame([], $db->singleQueries);
		$this->assertSame([], $db->nonQueries);
	}
}

require_once __DIR__ . '/../Support/RecordingDatabaseStub.php';
