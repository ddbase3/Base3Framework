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

class BaseAssetResolver implements IAssetResolver {

	public function resolve(string $path): string {
		$path = trim($path);

		if($path === '') {
			return '';
		}

		if($this->isAbsoluteOrSpecialUrl($path)) {
			return $path;
		}

		if($this->startsWith($path, './') || $this->startsWith($path, '../')) {
			return $path;
		}

		return './' . ltrim($path, '/');
	}

	private function isAbsoluteOrSpecialUrl(string $path): bool {
		if($this->startsWith($path, '/') || $this->startsWith($path, '#')) {
			return true;
		}

		if($this->startsWith($path, '//')) {
			return true;
		}

		return preg_match('/^[a-z][a-z0-9+\-.]*:/i', $path) === 1;
	}

	private function startsWith(string $value, string $prefix): bool {
		return substr($value, 0, strlen($prefix)) === $prefix;
	}

}
