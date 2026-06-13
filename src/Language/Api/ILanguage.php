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

namespace Base3\Language\Api;

/**
 * Interface ILanguage
 *
 * Provides methods for managing the current language and listing available languages.
 */
interface ILanguage {

	/**
	 * Returns the currently active language code.
	 *
	 * @return string Current language (e.g. "en", "de")
	 */
	public function getLanguage(): string;

	/**
	 * Sets the active language.
	 *
	 * @param string $language Language code to activate (e.g. "en", "de")
	 * @return void
	 */
	public function setLanguage(string $language);

	/**
	 * Returns the list of supported languages.
	 *
	 * @return array<string> List of language codes
	 */
	public function getLanguages(): array;

}

