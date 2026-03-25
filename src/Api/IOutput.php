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

namespace Base3\Api;

/**
 * Interface IOutput
 *
 * Defines an output component that can return data in various formats and provide usage help.
 */
interface IOutput extends IBase {

	/**
	 * Returns the output in the desired format.
	 *
	 * Common formats include "html", "json", "xml", "csv", "page", etc.
	 *
	 * @param string $out Desired output format (default is "html")
	 * @param bool $final Output is called directly like an endpoint (true), or output gets embedded (false)
	 * @return string Output data in the specified format
	 */
	public function getOutput(string $out = 'html', bool $final = false): string;

	/**
	 * Returns syntax help and debug information.
	 *
	 * Usually returns expected GET/POST parameters and optionally debug-related info.
	 * Only used or displayed if the system is in debug mode.
	 *
	 * @return mixed Help and debug information
	 */
	public function getHelp(): string;
}
