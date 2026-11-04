<?php declare(strict_types=1);

namespace Base3\Test\Cache;

use Base3\Cache\FileCache;
use Base3\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

final class FileCacheDownloadCoverageTest extends TestCase {

	private function createCache(RecordingDatabaseStub $db, ?ArrayConfiguration $configuration): FileCache {
		$previous = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$container->set('database', $db, ServiceLocator::SHARED);
		if ($configuration !== null) $container->set('configuration', $configuration, ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new FileCache(); }
		finally { ServiceLocator::useInstance($previous); }
	}

	private function withTemporaryCache(callable $test): void {
		$root = sys_get_temp_dir() . '/base3-cache-test-' . bin2hex(random_bytes(8));
		$previousDirectory = getcwd();
		mkdir($root, 0700);
		try {
			chdir($root);
			$test($root);
		} finally {
			chdir($previousDirectory);
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($iterator as $entry) {
				if ($entry->isDir()) rmdir($entry->getPathname());
				else unlink($entry->getPathname());
			}
			rmdir($root);
		}
	}

	public function testCacheMissDownloadsLocalSourceAndPersistsIndexedFile(): void {
		$this->withTemporaryCache(function(string $root): void {
			file_put_contents($root . '/source.txt', 'cached content');
			$url = 'file://' . $root . '/source.txt';
			$db = new RecordingDatabaseStub();
			$cache = $this->createCache($db, new ArrayConfiguration(['base' => ['url' => 'https://files.example/']]));
			$result = $cache->getCacheUrl($url);
			$this->assertCount(1, $result);
			$this->assertSame($url, array_key_first($result));
			$this->assertStringContainsString('https://files.example/userfiles/cache/', $result[$url]);
			$relativeFile = substr($result[$url], strlen('https://files.example/'));
			$this->assertSame('cached content', file_get_contents($root . '/' . $relativeFile));
			$this->assertSame(1, $db->connectCalls);
			$this->assertCount(1, $db->singleQueries);
			$this->assertCount(1, $db->nonQueries);
			$this->assertStringContainsString("INSERT INTO `cache`", $db->nonQueries[0]);
			$this->assertStringContainsString(sha1($url), $db->nonQueries[0]);
		});
	}

	public function testForcedRefreshDeletesCacheRecordAndRedownloadsWithoutLookup(): void {
		$this->withTemporaryCache(function(string $root): void {
			file_put_contents($root . '/source.png', "\x89PNGtest");
			$url = 'file://' . $root . '/source.png';
			$db = new RecordingDatabaseStub();
			$db->singleRow = ['value' => 'oldvalue', 'extension' => 'png'];
			$cache = $this->createCache($db, new ArrayConfiguration(['base' => ['url' => '']]));
			$result = $cache->getCacheUrls([$url], true);
			$this->assertCount(0, $db->singleQueries);
			$this->assertCount(2, $db->nonQueries);
			$this->assertStringContainsString('DELETE FROM `cache`', $db->nonQueries[0]);
			$this->assertStringContainsString('INSERT INTO `cache`', $db->nonQueries[1]);
			$this->assertSame("\x89PNGtest", file_get_contents($root . '/' . $result[$url]));
		});
	}

	public function testCacheWithoutBaseConfigurationReturnsRelativeFileUrl(): void {
		$this->withTemporaryCache(function(string $root): void {
			file_put_contents($root . '/image.jpg', 'jpeg bytes');
			$url = 'file://' . $root . '/image.jpg';
			$db = new RecordingDatabaseStub();
			$cache = $this->createCache($db, null);
			$result = $cache->getCacheUrl($url);
			$this->assertStringContainsString('userfiles/cache/', $result[$url]);
			$this->assertSame(0, strpos($result[$url], 'userfiles/cache/'));
			$this->assertSame('jpeg bytes', file_get_contents($root . '/' . $result[$url]));
		});
	}

	public function testMultipleColdEntriesUseIndependentHashesAndStoredPaths(): void {
		$this->withTemporaryCache(function(string $root): void {
			file_put_contents($root . '/first.txt', 'first');
			file_put_contents($root . '/second.txt', 'second');
			$urls = ['file://' . $root . '/first.txt', 'file://' . $root . '/second.txt'];
			$db = new RecordingDatabaseStub();
			$cache = $this->createCache($db, new ArrayConfiguration(['base' => ['url' => '']]));
			$result = $cache->getCacheUrls($urls);
			$this->assertSame($urls, array_keys($result));
			$this->assertNotSame($result[$urls[0]], $result[$urls[1]]);
			$this->assertSame('first', file_get_contents($root . '/' . $result[$urls[0]]));
			$this->assertSame('second', file_get_contents($root . '/' . $result[$urls[1]]));
			$this->assertCount(2, $db->singleQueries);
			$this->assertCount(2, $db->nonQueries);
		});
	}
}

require_once __DIR__ . '/../Support/RecordingDatabaseStub.php';
