<?php declare(strict_types=1);

namespace Base3\Test\Knowledge\Source;

use PHPUnit\Framework\TestCase;

final class KnowledgeSourceTest extends TestCase {

	public function testListsKnownScopesAndFields(): void {
		$source = new \Base3\Knowledge\Source\KnowledgeSource();
		$this->assertSame(['continent','country','language'], $source->getScopes());
		$this->assertSame(['id','name_de','name_en'], $source->getFields('continent'));
		$this->assertNull($source->getFields('unknown'));
		$this->assertNull($source->getData('unknown'));
	}

	public function testLoadsAvailableCountryAndContinentMetadata(): void {
		$source = new \Base3\Knowledge\Source\KnowledgeSource();
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
