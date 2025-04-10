<?php declare(strict_types=1);

namespace Base3\Language\MultiLang;

use Base3\Core\ServiceLocator;
use Base3\Language\Api\ILanguage;
use Base3\Api\ICheck;

class MultiLang implements ILanguage, ICheck {

	private $servicelocator;

	private $cnf;
	private $language;
	private $languages;

	public function __construct(\Base3\Configuration\Api\IConfiguration $configuration) {

		// refactoring, former param
		$cnf = null;

		$this->servicelocator = ServiceLocator::getInstance();

		// $configuration = $this->servicelocator->get('configuration');
		if ($configuration != null)
			$this->cnf = $configuration->get('language');

		$this->languages = $this->cnf != null && $this->cnf['languages'] != null ? $this->cnf['languages'] : [];

		if (isset($_SESSION["language"])) {
			$this->language = $_SESSION["language"];
		} else {
			if ($this->cnf != null) $this->language = $this->cnf["main"];
		}
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string $language) {
		if (!in_array($language, $this->cnf["languages"])) return;
		$this->language = $_SESSION["language"] = $language;
	}

	public function getLanguages(): array {
		return $this->languages;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null || $this->servicelocator->get('session') == null ? "Fail" : "Ok"
		);
	}

}
