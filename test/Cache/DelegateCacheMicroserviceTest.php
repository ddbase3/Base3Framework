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

use Base3\Core\Cache\DelegateCacheMicroservice;
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
