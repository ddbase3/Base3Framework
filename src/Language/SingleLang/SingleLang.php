<?php declare(strict_types=1);

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
