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

/**
 * Interface IPageModuleFooter
 *
 * Represents a page module that appears in the footer area and defines a display priority.
 */
interface IPageModuleFooter extends IPageModule {

	/**
	 * Returns the display priority of the module.
	 *
	 * Lower values may appear earlier depending on layout logic.
	 *
	 * @return int Module priority
	 */
	public function getPriority();

}

