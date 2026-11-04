<?php declare(strict_types=1);

namespace Base3\Test\Translation\NoTranslation;

use PHPUnit\Framework\TestCase;

final class NoTranslationTest extends TestCase {

	public function testFallbackAndKeyWithoutTranslations(): void {
		$t = new \Base3\Translation\NoTranslation\NoTranslation();
		$this->assertSame('missing.key', $t->translate('plugin', 'section', 'missing.key'));
		$this->assertSame('Shown', $t->translate('plugin', 'section', 'missing.key', 'Shown'));
	}

	public function testReplacesBareAndBracedTokens(): void {
		$t = new \Base3\Translation\NoTranslation\NoTranslation();
		$this->assertSame('Hello Ana and Ana', $t->translate('p', 's', 'k', 'Hello {name} and name', ['name' => 'Ana']));
		$this->assertSame('Count 0', $t->translate('p','s','k','Count {count}', ['count' => 0]));
	}

}
