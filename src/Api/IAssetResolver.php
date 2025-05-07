<?php declare(strict_types=1);

namespace Base3\Api;

interface IAssetResolver {

	/**
	 * Resolves a plugin asset path to the corresponding public path.
	 * E.g. plugin/Foo/assets/js/app.js → /assets/Foo/js/app.js
	 */
	public function resolve(string $path): string;
}
