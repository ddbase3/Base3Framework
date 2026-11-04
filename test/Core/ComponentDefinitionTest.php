<?php declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;

final class ComponentDefinitionTest extends TestCase {

	public function testConstructedDefinitionPreservesConfigAndProducesQualifiedServiceKey(): void {
		$definition = new \Base3\Core\ComponentDefinition('mailer', '\Base3\Api\IComponent', 'smtp', ['server'=>'example'], ['enabled'=>true], ['name'=>'Mail']);
		$this->assertSame('mailer', $definition->id);
		$this->assertSame('smtp', $definition->implementationName);
		$this->assertSame(['server'=>'example'], $definition->arguments);
		$this->assertSame(['enabled'=>true], $definition->config);
		$this->assertSame(['name'=>'Mail'], $definition->metadata);
		$this->assertSame('component.definition.Base3.Api.IComponent.mailer', $definition->getServiceName());
		$this->assertSame($definition->getServiceName(), \Base3\Core\ComponentDefinition::getServiceNameFor('Base3\Api\IComponent', 'mailer'));
	}

}
