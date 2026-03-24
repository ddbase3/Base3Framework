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

namespace Base3\Page\Api;

use Base3\Api\IBase;

/**
 * Interface IPageModuleDependent
 *
 * Represents a page module that declares dependencies on other modules.
 */
interface IPageModuleDependent extends IPageModule {

	/**
	 * Returns a list of required module names or identifiers.
	 *
	 * @return array<string> List of required module identifiers
	 */
	public function getRequiredModules();

}

