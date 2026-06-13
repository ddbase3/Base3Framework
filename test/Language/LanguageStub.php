<?php declare(strict_types=1);

namespace Base3\Test\Language;

use Base3\Language\Api\ILanguage;

/**
 * Class LanguageStub
 *
 * Simple, DI-free language stub for unit tests.
 */
class LanguageStub implements ILanguage {

	private string $language;
	private array $languages;

	public function __construct(string $language = 'en', array $languages = ['en', 'de']) {
		$this->language = $language;
		$this->languages = $languages;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string $language) {
		$this->language = $language;
		if (!in_array($language, $this->languages, true)) {
			$this->languages[] = $language;
		}
	}

	public function getLanguages(): array {
		return $this->languages;
	}
}
