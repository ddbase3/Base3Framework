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
 * Interface IPageModule
 *
 * Defines a reusable page module that can receive data and render HTML.
 */
interface IPageModule extends IBase {

	/**
	 * Sets the data used by the module.
	 *
	 * @param mixed $data Arbitrary data to be used in rendering (e.g. array, object)
	 * @return void
	 */
	public function setData($data);

	/**
	 * Returns the rendered HTML of the module.
	 *
	 * @return string HTML output
	 */
	public function getHtml();

}

