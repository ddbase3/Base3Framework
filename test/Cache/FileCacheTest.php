<?php declare(strict_types=1);

namespace Base3\Test\Cache;

use Base3\Cache\FileCache;
use Base3\Configuration\ArrayConfiguration;
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
