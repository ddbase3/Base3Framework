<?php declare(strict_types=1);

namespace Base3\Language\SingleLang;

use Base3\Language\Api\ILanguage;
use Base3\Configuration\Api\IConfiguration;

/* Only use language as configured as "main" */
class SingleLang implements ILanguage {

	private $language = null;

	public function __construct(IConfiguration $configuration) {
		$cnf = $configuration->get('language');
		if ($cnf != null) $this->language = $cnf["main"];
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string $language) {
		// do nothing
	}

	public function getLanguages(): array {
		return [ $this->language ];
	}
}
