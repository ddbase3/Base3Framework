<?php declare(strict_types=1);

namespace Base3\ServiceSelector\LangBased;

use Base3\Api\IContainer;
use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Language-aware service selector for multi-language applications.
 *
 * Uses the "data" parameter to switch language context.
 */
class LangBasedServiceSelector extends AbstractServiceSelector {

	public function __construct(protected IContainer $container) {
		parent::__construct($container);
	}

	/**
	 * Sets the language from the "data" request parameter if valid (e.g. "en", "de").
	 *
	 * @param string $data Request data parameter
	 */
	protected function handleLanguage(string $data): void {
		if (strlen($data) === 2) {
			$language = $this->container->get('language');
			$language->setLanguage($data);
		}
	}
}
