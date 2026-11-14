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

final class ComponentDefinitionTest extends TestCase {

	public function testConstructedDefinitionPreservesConfigAndProducesQualifiedServiceKey(): void {
		$definition = new \Base3\CoreFoundation\Dto\ComponentDefinition('mailer', '\Base3\CoreFoundation\Api\IComponent', 'smtp', ['server'=>'example'], ['enabled'=>true], ['name'=>'Mail']);
		$this->assertSame('mailer', $definition->id);
		$this->assertSame('smtp', $definition->implementationName);
		$this->assertSame(['server'=>'example'], $definition->arguments);
		$this->assertSame(['enabled'=>true], $definition->config);
		$this->assertSame(['name'=>'Mail'], $definition->metadata);
		$this->assertSame('component.definition.Base3.CoreFoundation.Api.IComponent.mailer', $definition->getServiceName());
		$this->assertSame($definition->getServiceName(), \Base3\CoreFoundation\Dto\ComponentDefinition::getServiceNameFor('Base3\CoreFoundation\Api\IComponent', 'mailer'));
	}

}
