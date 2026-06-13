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
