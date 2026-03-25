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

namespace Base3\LinkTarget\PrettyName;

use Base3\LinkTarget\Api\ILinkTargetService;

/**
 * Pretty-name based link target service for BASE3.
 *
 * Example:
 * - target: ['name' => 'imprint', 'out' => 'html']
 * - params: ['a' => 1]
 * - result: imprint.html?a=1
 *
 * These links are intended to be rewritten by the web server to the standard
 * BASE3 query format.
 *
 * If "out" is omitted, "php" is used.
 */
class PrettyNameLinkTargetService implements ILinkTargetService {

	/**
	 * Builds a pretty-name BASE3 link.
	 *
	 * @param array<string,mixed> $target
	 * @param array<string,mixed> $params
	 * @return string
	 */
	public function getLink(array $target, array $params = []): string {
		$name = (string) ($target['name'] ?? '');
		$out = (string) ($target['out'] ?? 'php');

		$link = rawurlencode($name) . '.' . rawurlencode($out);

		if (!empty($params)) {
			$link .= '?' . http_build_query($params);
		}

		return $link;
	}

}
