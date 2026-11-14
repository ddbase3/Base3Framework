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

final class LangBasedServiceSelectorTest extends TestCase {

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

	public function testSelectsTwoLetterLanguageOnly(): void {
		[$container,$map,$language]=$this->setupServices(['data'=>'fr']);
		$map->register('index',new SelectorTestOutput());
		$s=new \Base3\Core\ServiceSelector\LangBased\LangBasedServiceSelector($container);
		$this->assertSame('response:html',$s->process());
		$this->assertSame(['fr'],$language->selected);
		$container->set(\Base3\CoreFoundation\Api\IRequest::class,new SelectorTestRequest(['data'=>'english']));
		$this->assertSame('response:html',$s->process());
		$this->assertSame(['fr'],$language->selected);
	}

	public function testUsesParentOutputWhenDataIsEmpty(): void {
		[$container,$map,$language]=$this->setupServices(['name'=>'profile']);
		$map->register('profile',new SelectorTestOutput());
		$this->assertSame('response:html',(new \Base3\Core\ServiceSelector\LangBased\LangBasedServiceSelector($container))->go());
		$this->assertSame([],$language->selected);
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
