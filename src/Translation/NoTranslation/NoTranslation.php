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

namespace Base3\Translation\NoTranslation;

use Base3\Translation\Api\ITranslation;

/**
 * Class NoTranslation
 *
 * Safe fallback translation implementation.
 */
class NoTranslation implements ITranslation {

	public function translate(string $set, string $section, string $key, string $fallback = '', array $replacements = []): string {
		$text = $fallback !== '' ? $fallback : $key;

		return $this->applyReplacements($text, $replacements);
	}

	/**
	 * @param array<string, scalar|null> $replacements
	 */
	protected function applyReplacements(string $text, array $replacements): string {
		if(empty($replacements)) {
			return $text;
		}

		$prepared = [];

		foreach($replacements as $key => $value) {
			$value = (string) $value;
			$key = (string) $key;

			$prepared[$key] = $value;
			$prepared['{' . $key . '}'] = $value;
		}

		return strtr($text, $prepared);
	}
}
