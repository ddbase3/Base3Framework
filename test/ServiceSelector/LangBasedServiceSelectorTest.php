<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use PHPUnit\Framework\TestCase;

final class LangBasedServiceSelectorTest extends TestCase {

private function setupServices(array $options = []): array {
		$container = new \Base3\Test\Core\ContainerStub();
		$map = new \Base3\Test\Support\StaticClassMapStub();
		$language = new SelectorTestLanguage();
		$container->set('configuration', new \Base3\Configuration\ArrayConfiguration(['base' => ['url'=>'https://example.test/','intern'=>'']]));
		$container->set('classmap', $map);
		$container->set('accesscontrol', new \Base3\Accesscontrol\No\NoAccesscontrol());
		$container->set(\Base3\Api\IRequest::class, new SelectorTestRequest($options));
		$container->set('middlewares', []);
		$container->set('language', $language);
		return [$container,$map,$language];
	}

	public function testSelectsTwoLetterLanguageOnly(): void {
		[$container,$map,$language]=$this->setupServices(['data'=>'fr']);
		$map->register('index',new SelectorTestOutput());
		$s=new \Base3\ServiceSelector\LangBased\LangBasedServiceSelector($container);
		$this->assertSame('response:html',$s->process());
		$this->assertSame(['fr'],$language->selected);
		$container->set(\Base3\Api\IRequest::class,new SelectorTestRequest(['data'=>'english']));
		$this->assertSame('response:html',$s->process());
		$this->assertSame(['fr'],$language->selected);
	}

	public function testUsesParentOutputWhenDataIsEmpty(): void {
		[$container,$map,$language]=$this->setupServices(['name'=>'profile']);
		$map->register('profile',new SelectorTestOutput());
		$this->assertSame('response:html',(new \Base3\ServiceSelector\LangBased\LangBasedServiceSelector($container))->go());
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
