<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IAssetResolver;

class BaseAssetResolver implements IAssetResolver {

	public function resolve(string $path): string {
		return $path;
	}
}

