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

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use Base3\Test\Core\ContainerStub;
use Base3\Test\Core\ResolverTestComponent;

final class ComponentResolverTest extends TestCase {

	private function createResolverFixture(): array {
		$container=new ContainerStub();
		$map=new \Base3\Test\Support\StaticClassMapStub();
		$resolver=new \Base3\Core\ComponentResolver($container,$map);
		return [$container,$map,$resolver];
	}

	public function testFindsDefinitionButDoesNotInventMissingImplementations(): void {
		[$container,$map,$r]=$this->createResolverFixture();
		$def=new \Base3\CoreFoundation\Dto\ComponentDefinition('a',\Base3\CoreFoundation\Api\IComponent::class,'example');
		$container->set($def->getServiceName(),$def);
		$container->set(\Base3\CoreFoundation\Dto\ComponentDefinition::SERVICE_PREFIX.'invalid',new \stdClass());
		$this->assertTrue($r->has(\Base3\CoreFoundation\Api\IComponent::class,'a'));
		$this->assertFalse($r->has(\Base3\CoreFoundation\Api\IComponent::class,'missing'));
		$this->assertFalse($r->has('Other','a'));
		$this->assertNull($r->get(\Base3\CoreFoundation\Api\IComponent::class,'a'));
		$this->assertSame([],iterator_to_array($r->all(\Base3\CoreFoundation\Api\IComponent::class)));
	}

	public function testInstantiatesConfiguredComponentWithDeclaredArgumentPrecedence(): void {
		[$container,$map,$r]=$this->createResolverFixture();
		$comp=new ResolverTestComponent('configured-id');
		$map->register('mycomponent',$comp);
		$map->instantiateResult=$comp;
		$def=new \Base3\CoreFoundation\Dto\ComponentDefinition('configured-id',\Base3\CoreFoundation\Api\IComponent::class,'mycomponent',['config'=>'caller','option'=>123],['secret'=>'none']);
		$container->set($def->getServiceName(),$def);
		$this->assertSame($comp,$r->get(\Base3\CoreFoundation\Api\IComponent::class,'configured-id'));
		$this->assertSame($comp,iterator_to_array($r->all(\Base3\CoreFoundation\Api\IComponent::class))[0]);
		$this->assertSame($comp::class,$map->instantiateWithCalls[0][0]);
		$args=$map->instantiateWithCalls[0][1];
		$this->assertSame('caller',$args['config']);
		$this->assertSame(123,$args['option']);
		$this->assertSame($def,$args['definition']);
		$this->assertSame($def,$args[\Base3\CoreFoundation\Dto\ComponentDefinition::class]);
	}

	public function testRejectsNonComponentInstancesAndIgnoresUnrelatedServices(): void {
		[$container,$map,$r]=$this->createResolverFixture();
		$comp=new ResolverTestComponent('unit');
		$map->register('impl',$comp);
		$map->instantiateResult=new \stdClass();
		$def=new \Base3\CoreFoundation\Dto\ComponentDefinition('unit',\Base3\CoreFoundation\Api\IComponent::class,'impl');
		$container->set('random.setting',$def);
		$this->assertFalse($r->has(\Base3\CoreFoundation\Api\IComponent::class,'unit'));
		$container->set($def->getServiceName(),$def);
		$this->assertNull($r->get(\Base3\CoreFoundation\Api\IComponent::class,'unit'));
		$this->assertSame([],iterator_to_array($r->all(\Base3\CoreFoundation\Api\IComponent::class)));
	}

}

require_once __DIR__ . '/ContainerStub.php';
require_once __DIR__ . '/ResolverTestComponent.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
