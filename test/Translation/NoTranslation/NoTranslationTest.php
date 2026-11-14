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

namespace Base3\Test\Translation\NoTranslation;

use PHPUnit\Framework\TestCase;

final class NoTranslationTest extends TestCase {

	public function testFallbackAndKeyWithoutTranslations(): void {
		$t = new \Base3\Core\Translation\NoTranslation\NoTranslation();
		$this->assertSame('missing.key', $t->translate('plugin', 'section', 'missing.key'));
		$this->assertSame('Shown', $t->translate('plugin', 'section', 'missing.key', 'Shown'));
	}

	public function testReplacesBareAndBracedTokens(): void {
		$t = new \Base3\Core\Translation\NoTranslation\NoTranslation();
		$this->assertSame('Hello Ana and Ana', $t->translate('p', 's', 'k', 'Hello {name} and name', ['name' => 'Ana']));
		$this->assertSame('Count 0', $t->translate('p','s','k','Count {count}', ['count' => 0]));
	}

}
