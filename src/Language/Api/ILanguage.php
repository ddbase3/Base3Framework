<?php declare(strict_types=1);

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

