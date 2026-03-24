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

namespace Base3\Core;

use Base3\Api\IAssetResolver;

class AssetResolver implements IAssetResolver {

	public function resolve(string $path): string {
		if (!str_starts_with($path, 'plugin/')) {
			return $path;
		}

		$parts = explode('/', $path);
		if (count($parts) < 4 || $parts[2] !== 'assets') {
			return $path;
		}

		$plugin = $parts[1];
		$subpath = array_slice($parts, 3); // skip plugin, PluginName, assets
		$target = 'assets/' . $plugin . '/' . implode('/', $subpath);

		// Optionally add cache-busting query param
		$realfile = DIR_ROOT . implode(DIRECTORY_SEPARATOR, $parts);
		$hash = file_exists($realfile) ? substr(md5_file($realfile), 0, 6) : '000000';
		return $target . '?t=' . $hash;
	}
}
