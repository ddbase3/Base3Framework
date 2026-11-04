<?php declare(strict_types=1);

namespace Base3\Test\Language\SingleLang;

use PHPUnit\Framework\TestCase;

final class SingleLangTest extends TestCase {

	public function testUsesMainLanguageAndIgnoresSwitches(): void {
		$l = new \Base3\Language\SingleLang\SingleLang(new \Base3\Configuration\ArrayConfiguration(['language' => ['main' => 'de']]));
		$this->assertSame('de', $l->getLanguage());
		$this->assertSame(['de'], $l->getLanguages());
		$l->setLanguage('en');
		$this->assertSame('de', $l->getLanguage());
	}

	public function testFallsBackToEnglish(): void {
		$l = new \Base3\Language\SingleLang\SingleLang(new \Base3\Configuration\ArrayConfiguration());
		$this->assertSame('en', $l->getLanguage());
	}

}
