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

namespace Base3\Language\SingleLang;

use Base3\Language\Api\ILanguage;
use Base3\Configuration\Api\IConfiguration;

/* Only use language as configured as "main" */
class SingleLang implements ILanguage {

	private $configuration;
	private $language = null;

	public function __construct(IConfiguration $configuration) {
		$this->configuration = $configuration;
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		if ($this->language === null) {
			$cnf = $this->configuration->get('language');
			$this->language = $cnf['main'] ?? 'en';
		}
		return $this->language;
	}

	public function setLanguage(string $language) {
		// do nothing
	}

	public function getLanguages(): array {
		return [ $this->getLanguage() ];
	}
}
