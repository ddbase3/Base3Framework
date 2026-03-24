<?php declare(strict_types=1);

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
