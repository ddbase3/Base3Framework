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
 * Defines a routable output component that can render or return data in
 * one or more output formats.
 *
 * Implementations of this interface represent endpoint-like components
 * that are resolved by the framework routing and invoked to produce the
 * final response body or embedded content.
 *
 * Typical output formats include:
 * - html
 * - json
 * - xml
 * - csv
 * - txt
 * - page
 *
 * The exact set of supported formats depends on the implementing class.
 *
 * Important design note:
 * This interface only defines rendering/output behavior. Optional help,
 * debug information, or self-description are intentionally not part of
 * IOutput anymore and should be provided separately through IHelp.
 */
interface IOutput extends IBase {

	/**
	 * Returns the output in the requested format.
	 *
	 * The $out parameter selects the desired output format. Implementations
	 * may support one or many formats and are responsible for handling
	 * unsupported values in a framework-appropriate way.
	 *
	 * The $final flag indicates whether the output is called directly as a
	 * routed endpoint or whether it is rendered as a nested/embedded part
	 * inside another output component.
	 *
	 * Example use cases:
	 * - final=true  -> direct endpoint call via router
	 * - final=false -> embedded rendering inside a page or layout
	 *
	 * @param string $out Desired output format, for example "html" or "json"
	 * @param bool $final True if called as final routed output, false if embedded
	 * @return string Rendered output content
	 */
	public function getOutput(string $out = 'html', bool $final = false): string;
}
