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

namespace Base3\Test\Knowledge\Source;

use PHPUnit\Framework\TestCase;

final class KnowledgeSourceTest extends TestCase {

	public function testListsKnownScopesAndFields(): void {
		$source = new \Base3\Core\Knowledge\Source\KnowledgeSource();
		$this->assertSame(['continent','country','language'], $source->getScopes());
		$this->assertSame(['id','name_de','name_en'], $source->getFields('continent'));
		$this->assertNull($source->getFields('unknown'));
		$this->assertNull($source->getData('unknown'));
	}

	public function testLoadsAvailableCountryAndContinentMetadata(): void {
		$source = new \Base3\Core\Knowledge\Source\KnowledgeSource();
		$continentIds = $source->getData('continent');
		$this->assertTrue(count($continentIds) > 0);
		$continentEntries = $source->getData('continent', ['id','name_en','name_de']);
		$this->assertCount(count($continentIds), $continentEntries);
		$this->assertArrayHasKey('name_en', reset($continentEntries));
		$this->assertArrayHasKey('name_de', reset($continentEntries));
		$country = $source->getData('country', ['id','name_en']);
		$this->assertTrue(count($country) > 0);
		$this->assertArrayHasKey('name_en', reset($country));
	}

}
