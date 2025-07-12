<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IAssetResolver
 *
 * Resolves internal plugin asset paths to public-facing asset URLs.
 */
interface IAssetResolver {

	/**
	 * Resolves a plugin asset path to the corresponding public path.
	 *
	 * For example: "plugin/Foo/assets/js/app.js" → "/assets/Foo/js/app.js"
	 *
	 * @param string $path Internal plugin-relative asset path
	 * @return string Publicly accessible asset path (usually web-relative URL)
	 */
	public function resolve(string $path): string;

}

