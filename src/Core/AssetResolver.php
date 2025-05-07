<?php declare(strict_types=1);

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

