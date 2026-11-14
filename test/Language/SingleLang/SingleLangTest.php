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

namespace Base3\Test\Language\SingleLang;

use PHPUnit\Framework\TestCase;

final class SingleLangTest extends TestCase {

	public function testUsesMainLanguageAndIgnoresSwitches(): void {
		$l = new \Base3\Core\Language\SingleLang\SingleLang(new \Base3\Core\Configuration\ArrayConfiguration(['language' => ['main' => 'de']]));
		$this->assertSame('de', $l->getLanguage());
		$this->assertSame(['de'], $l->getLanguages());
		$l->setLanguage('en');
		$this->assertSame('de', $l->getLanguage());
	}

	public function testFallsBackToEnglish(): void {
		$l = new \Base3\Core\Language\SingleLang\SingleLang(new \Base3\Core\Configuration\ArrayConfiguration());
		$this->assertSame('en', $l->getLanguage());
	}

}
