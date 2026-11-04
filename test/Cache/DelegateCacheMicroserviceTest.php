<?php declare(strict_types=1);

namespace Base3\Test\Cache;

use Base3\Cache\DelegateCacheMicroservice;
use Base3\Core\ServiceLocator;
use PHPUnit\Framework\TestCase;

final class DelegateCacheMicroserviceTest extends TestCase {

	public function testSingleUrlIsDelegatedToConfiguredCache(): void {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$backend=new RecordingCacheFixture();
		$container->set('cache',$backend,ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $proxy=new DelegateCacheMicroservice(); }
		finally { ServiceLocator::useInstance($previous); }
		$this->assertSame('cached-url',$proxy->getCacheUrl('https://example.invalid/a',true));
		$this->assertSame([['one','https://example.invalid/a',true]],$backend->calls);
	}
}

require_once __DIR__ . '/RecordingCacheFixture.php';
