<?php declare(strict_types=1);

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
		$def=new \Base3\Core\ComponentDefinition('a',\Base3\Api\IComponent::class,'example');
		$container->set($def->getServiceName(),$def);
		$container->set(\Base3\Core\ComponentDefinition::SERVICE_PREFIX.'invalid',new \stdClass());
		$this->assertTrue($r->has(\Base3\Api\IComponent::class,'a'));
		$this->assertFalse($r->has(\Base3\Api\IComponent::class,'missing'));
		$this->assertFalse($r->has('Other','a'));
		$this->assertNull($r->get(\Base3\Api\IComponent::class,'a'));
		$this->assertSame([],iterator_to_array($r->all(\Base3\Api\IComponent::class)));
	}

	public function testInstantiatesConfiguredComponentWithDeclaredArgumentPrecedence(): void {
		[$container,$map,$r]=$this->createResolverFixture();
		$comp=new ResolverTestComponent('configured-id');
		$map->register('mycomponent',$comp);
		$map->instantiateResult=$comp;
		$def=new \Base3\Core\ComponentDefinition('configured-id',\Base3\Api\IComponent::class,'mycomponent',['config'=>'caller','option'=>123],['secret'=>'none']);
		$container->set($def->getServiceName(),$def);
		$this->assertSame($comp,$r->get(\Base3\Api\IComponent::class,'configured-id'));
		$this->assertSame($comp,iterator_to_array($r->all(\Base3\Api\IComponent::class))[0]);
		$this->assertSame($comp::class,$map->instantiateWithCalls[0][0]);
		$args=$map->instantiateWithCalls[0][1];
		$this->assertSame('caller',$args['config']);
		$this->assertSame(123,$args['option']);
		$this->assertSame($def,$args['definition']);
		$this->assertSame($def,$args[\Base3\Core\ComponentDefinition::class]);
	}

	public function testRejectsNonComponentInstancesAndIgnoresUnrelatedServices(): void {
		[$container,$map,$r]=$this->createResolverFixture();
		$comp=new ResolverTestComponent('unit');
		$map->register('impl',$comp);
		$map->instantiateResult=new \stdClass();
		$def=new \Base3\Core\ComponentDefinition('unit',\Base3\Api\IComponent::class,'impl');
		$container->set('random.setting',$def);
		$this->assertFalse($r->has(\Base3\Api\IComponent::class,'unit'));
		$container->set($def->getServiceName(),$def);
		$this->assertNull($r->get(\Base3\Api\IComponent::class,'unit'));
		$this->assertSame([],iterator_to_array($r->all(\Base3\Api\IComponent::class)));
	}

}

require_once __DIR__ . '/ContainerStub.php';
require_once __DIR__ . '/ResolverTestComponent.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
