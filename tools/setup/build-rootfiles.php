<?php

define('DIR_ROOT', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);

echo "🔍 Searching for plugin rootfiles...\n";

$pluginDirs = glob(DIR_PLUGIN . '*', GLOB_ONLYDIR);
foreach ($pluginDirs as $pluginDir) {
	$pluginName = basename($pluginDir);
	$rootfilesSource = $pluginDir . DIRECTORY_SEPARATOR . 'rootfiles';

	if (!is_dir($rootfilesSource)) {
		echo "⏭️  Skipping $pluginName (no rootfiles/ directory)\n";
		continue;
	}

	echo "📄 Copying rootfiles from $pluginName...\n";
	recurseCopy($rootfilesSource, DIR_ROOT);
}

echo "✅ Done.\n";

/**
 * Recursively copies a directory tree and applies permissions.
 */
function recurseCopy(string $src, string $dst): void {
	if (!is_dir($dst)) {
		mkdir($dst, 0755, true);
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
			chmod($dstPath, 0755);
		} else {
			copy($srcPath, $dstPath);
			chmod($dstPath, 0644);
		}
	}
}
