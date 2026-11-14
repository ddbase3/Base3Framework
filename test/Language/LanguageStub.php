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

namespace Base3\Test\Language;

use Base3\CoreFoundation\Language\Api\ILanguage;

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
