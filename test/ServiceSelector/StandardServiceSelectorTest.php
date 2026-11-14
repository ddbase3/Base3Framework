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

namespace Base3\Test\ServiceSelector;

use PHPUnit\Framework\TestCase;

final class StandardServiceSelectorTest extends TestCase {

private function setupServices(array $options = []): array {
		$container = new \Base3\Test\Core\ContainerStub();
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$language = new SelectorTestLanguage();
		$container->set('configuration', new \Base3\Core\Configuration\ArrayConfiguration(['base' => ['url'=>'https://example.test/','intern'=>'']]));
		$container->set('classmap', $map);
		$container->set('accesscontrol', new \Base3\Core\Accesscontrol\No\NoAccesscontrol());
		$container->set(\Base3\CoreFoundation\Api\IRequest::class, new SelectorTestRequest($options));
		$container->set('middlewares', []);
		$container->set('language', $language);
		return [$container,$map,$language];
	}

	public function testDispatchesRegisteredOutputWithDefaults(): void {
		[$container,$map] = $this->setupServices();
		$output = new SelectorTestOutput();$map->register('index',$output);
		$selector=new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container);
		$this->assertSame('response:html',$selector->go());
		$this->assertSame([['html',true]],$output->calls);
	}

	public function testDispatchesAppScopedAndNamedOutput(): void {
		[$container,$map] = $this->setupServices(['app'=>'example','name'=>'main']);
		$output=new SelectorTestOutput();$map->register('main',$output);
		$this->assertSame('response:html',(new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container))->process());
	}

	public function testCatchallAndMissingHelpDoesNotRenderPage(): void {
		[$container,$map]=$this->setupServices(['name'=>'missing','out'=>'help']);
		$page=new SelectorTestPage();$map->register('fallback',$page);
		$this->assertSame('',(new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container))->process());
		$this->assertSame([],$page->calls);
	}

	public function testFallbackPageRendersWhenOutputMissing(): void {
		[$container,$map]=$this->setupServices(['name'=>'missing']);
		$page=new SelectorTestPage();$map->register('fallback',$page);
		$this->assertSame('catchall:html',(new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container))->process());
		$this->assertSame([['html',true]],$page->calls);
	}

	public function testHelpIsHiddenWithoutDebugAndShownWithDebug(): void {
		[$container,$map]=$this->setupServices(['out'=>'help']);
		$map->register('index',new SelectorTestOutput());
		$selector=new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container);
		$debug=getenv('DEBUG');
		try {
			putenv('DEBUG');
			$this->assertSame('',$selector->process());
			putenv('DEBUG=1');
			$this->assertSame('selector help',$selector->process());
		} finally {
			if ($debug === false) putenv('DEBUG');
			else putenv('DEBUG='.$debug);
		}
	}

	public function testMiddlewareOrderAndTerminalSelector(): void {
		[$container,$map]=$this->setupServices();
		$map->register('index',new SelectorTestOutput());
		$first=new SelectorTestMiddleware('a');$second=new SelectorTestMiddleware('b');
		$container->set('middlewares',[$first,$second]);
		$s=new \Base3\Core\ServiceSelector\Standard\StandardServiceSelector($container);
		$this->assertSame('a[b[response:html]]',$s->go());
		$this->assertSame(1,$first->calls);
		$this->assertSame(1,$second->calls);
		$s->setNext($first);
		$this->assertSame('response:html',$s->process());
	}

}

require_once __DIR__ . '/SelectorTestRequest.php';
require_once __DIR__ . '/SelectorTestOutput.php';
require_once __DIR__ . '/SelectorTestPage.php';
require_once __DIR__ . '/SelectorTestRoute.php';
require_once __DIR__ . '/SelectorTestMiddleware.php';
require_once __DIR__ . '/SelectorTestLanguage.php';
require_once __DIR__ . '/../Core/ContainerStub.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
