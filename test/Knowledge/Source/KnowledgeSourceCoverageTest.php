<?php declare(strict_types=1);

namespace Base3\Test\Knowledge\Source;

use Base3\Knowledge\Source\KnowledgeSource;
use PHPUnit\Framework\TestCase;

final class KnowledgeSourceCoverageTest extends TestCase {

	public function testContinentIdsAreConsistentWithFullProjection(): void {
		$source = new KnowledgeSource();
		$ids = $source->getData('continent');
		$rows = $source->getData('continent', ['id']);
		$this->assertSame($ids, array_keys($rows));
		foreach ($rows as $id => $row) $this->assertSame(['id' => $id], $row);
	}

	public function testContinentEnglishAndGermanNamesAreIndependentProjections(): void {
		$source = new KnowledgeSource();
		$en = $source->getData('continent', ['name_en']);
		$de = $source->getData('continent', ['name_de']);
		$this->assertSame(['name_en' => 'Europe'], $en['EU']);
		$this->assertSame(['name_de' => 'Europa'], $de['EU']);
		$this->assertCount(count($en), $de);
	}

	public function testCountryAllFieldsAreProjectedToTheirDocumentedNames(): void {
		$source = new KnowledgeSource();
		$fields = $source->getFields('country');
		$rows = $source->getData('country', $fields);
		$this->assertCount(count($source->getData('country')), $rows);
		$this->assertSame($fields, array_keys($rows['AD']));
		$this->assertSame('AD', $rows['AD']['id']);
		$this->assertSame('Andorra', $rows['AD']['name_en']);
		$this->assertSame('EU', $rows['AD']['continent_id']);
		$this->assertSame('EUR', $rows['AD']['currency']);
		$this->assertSame(['ca'], $rows['AD']['language_ids']);
	}

	public function testCountrySingleFieldProjectionDoesNotLeakOtherFields(): void {
		$source = new KnowledgeSource();
		$this->assertSame(['phone' => '376'], $source->getData('country', ['phone'])['AD']);
		$this->assertSame(['capital_en' => 'Andorra la Vella'], $source->getData('country', ['capital_en'])['AD']);
		$this->assertSame(['name_native' => 'Andorra'], $source->getData('country', ['name_native'])['AD']);
	}

	public function testLanguageIdsAndAllPublicFieldsAreReturned(): void {
		$source = new KnowledgeSource();
		$ids = $source->getData('language');
		$this->assertTrue(in_array('ar', $ids, true));
		$this->assertTrue(in_array('de', $ids, true));
		$fields = $source->getFields('language');
		$row = $source->getData('language', $fields)['ar'];
		$this->assertSame($fields, array_keys($row));
		$this->assertSame('ar', $row['id']);
		$this->assertSame(1, $row['rtl']);
		$this->assertTrue(in_array('AE', $row['country_ids'], true));
	}

	public function testLanguageRtlFlagDefaultsToZeroForNonRtlLanguages(): void {
		$source = new KnowledgeSource();
		$this->assertSame(['rtl' => 0], $source->getData('language', ['rtl'])['de']);
		$this->assertSame(['rtl' => 1], $source->getData('language', ['rtl'])['ar']);
	}

	public function testLanguageCountriesAreDerivedFromCountryLanguageMembership(): void {
		$source = new KnowledgeSource();
		$countries = $source->getData('language', ['country_ids']);
		$this->assertTrue(in_array('DE', $countries['de']['country_ids'], true));
		$this->assertTrue(in_array('FR', $countries['fr']['country_ids'], true));
		$this->assertFalse(in_array('DE', $countries['ar']['country_ids'], true));
	}

	public function testLanguageNamesAreReturnedWithoutCountryCrossReferenceWhenNotRequested(): void {
		$source = new KnowledgeSource();
		$row = $source->getData('language', ['id', 'name_native', 'name_en'])['de'];
		$this->assertSame(['id', 'name_native', 'name_en'], array_keys($row));
		$this->assertSame('de', $row['id']);
		$this->assertSame('German', $row['name_en']);
	}

	public function testEmptyFieldListIsEquivalentToOmittedFieldSelection(): void {
		$source = new KnowledgeSource();
		foreach (['continent', 'country', 'language'] as $scope) {
			$ids = $source->getData($scope, []);
			$this->assertTrue(count($ids) > 0);
			$this->assertSame($source->getData($scope), $ids);
		}
	}

	public function testUnknownRequestedFieldsAreIgnored(): void {
		$source = new KnowledgeSource();
		foreach (['continent', 'country', 'language'] as $scope) {
			$rows = $source->getData($scope, ['not_a_public_field']);
			$this->assertSame([], reset($rows));
		}
	}

	public function testUnsupportedScopeAndInvalidFieldTypesAreRejected(): void {
		$source = new KnowledgeSource();
		$this->assertNull($source->getData('unknown', ['id']));
		$this->assertNull($source->getData('unknown', null));
		$this->assertNull($source->getData('country', 'id'));
		$this->assertNull($source->getData('language', 1));
	}
}
