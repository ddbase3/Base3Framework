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

final class RoutingServiceSelectorTest extends TestCase {

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

	public function testFirstMatchingRouteUsesNormalizedRequestPath(): void {
		[$container,$map,$language]=$this->setupServices();
		$first=new SelectorTestRoute('/other','wrong');
		$second=new SelectorTestRoute('/page','selected');
		$third=new SelectorTestRoute('/page','never');
		$container->set('routes',[$first,$second,$third]);
		$old=$_SERVER['REQUEST_URI'] ?? null;
		try {
			$_SERVER['REQUEST_URI']='page?token=abc';
			$this->assertSame('selected',(new \Base3\Core\ServiceSelector\Routing\RoutingServiceSelector($container))->process());
			$this->assertSame(['/page'],$first->examinedPaths);
			$this->assertSame(['/page'],$second->examinedPaths);
			$this->assertSame([],$third->examinedPaths);
			$this->assertSame(['de'],$language->selected);
			$this->assertSame('de',$second->dispatched[0]['data']);
		} finally {
			if ($old === null) unset($_SERVER['REQUEST_URI']);
			else $_SERVER['REQUEST_URI']=$old;
		}
	}

	public function testClosureRoutesAndUnmatchedRouteFallsBackToOutput(): void {
		[$container,$map,$language]=$this->setupServices(['data'=>'it']);
		$map->register('index',new SelectorTestOutput());
		$found=new SelectorTestRoute('/different','should not render');
		$container->set('routes',static fn():array => ['unresolvable-route',$found]);
		$old=$_SERVER['REQUEST_URI'] ?? null;
		try {
			$_SERVER['REQUEST_URI']='/index.php?hello=yes';
			$this->assertSame('response:html',(new \Base3\Core\ServiceSelector\Routing\RoutingServiceSelector($container))->process());
			$this->assertSame(['/'],$found->examinedPaths);
			$this->assertSame(['it'],$language->selected);
		} finally {
			if ($old === null) unset($_SERVER['REQUEST_URI']);
			else $_SERVER['REQUEST_URI']=$old;
		}
	}

	public function testUnknownRouteEntriesAreIgnored(): void {
		[$container,$map]=$this->setupServices();
		$map->register('index',new SelectorTestOutput());
		$container->set('routes',[new \stdClass(),123,[]]);
		$old=$_SERVER['REQUEST_URI'] ?? null;
		try {
			unset($_SERVER['REQUEST_URI']);
			$this->assertSame('response:html',(new \Base3\Core\ServiceSelector\Routing\RoutingServiceSelector($container))->process());
		} finally {
			if ($old !== null) $_SERVER['REQUEST_URI']=$old;
		}
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
