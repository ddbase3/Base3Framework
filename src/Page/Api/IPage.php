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

use Base3\Api\IOutput;

/**
 * Interface IPage
 *
 * Represents a renderable page that also provides a public URL.
 * Inherits output behavior from IOutput.
 */
interface IPage extends IOutput {

	/**
	 * Returns the public URL of the page.
	 *
	 * @return string|null The URL of the page, or null if not available
	 */
	public function getUrl();

}

