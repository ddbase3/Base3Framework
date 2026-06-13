<?php

define('DIR_ROOT', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_PUBLIC_ASSETS', DIR_ROOT . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR);

@mkdir(DIR_PUBLIC_ASSETS, 0777, true); // Ensure the target directory exists

echo "🔍 Searching for plugin assets...\n";

$pluginDirs = glob(DIR_PLUGIN . '*', GLOB_ONLYDIR);
foreach ($pluginDirs as $pluginDir) {
	$pluginName = basename($pluginDir);
	$assetsSource = $pluginDir . DIRECTORY_SEPARATOR . 'assets';
	$assetsTarget = DIR_PUBLIC_ASSETS . $pluginName;

	if (!is_dir($assetsSource)) {
		echo "⏭️  Skipping $pluginName (no assets/ directory)\n";
		continue;
	}

	echo "📁 Copying assets from $pluginName...\n";
	recurseCopy($assetsSource, $assetsTarget);
}

echo "✅ Done.\n";

/**
 * Recursively copies a directory tree.
 */
function recurseCopy(string $src, string $dst): void {
	if (!is_dir($dst)) {
		mkdir($dst, 0777, true);
	}

	$items = scandir($src);
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}

		$srcPath = $src . DIRECTORY_SEPARATOR . $item;
		$dstPath = $dst . DIRECTORY_SEPARATOR . $item;

		if (is_dir($srcPath)) {
			recurseCopy($srcPath, $dstPath);
		} else {
			copy($srcPath, $dstPath);
		}
	}
}
