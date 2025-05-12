<?php

/**
 * Merges all plugin composer.json files that opt in via "_base3.merge": true
 * into a single composer.json located at: plugin/composer.json
 */

$rootDir = dirname(__DIR__);
$pluginDir = $rootDir . '/plugin';
$outputFile = $rootDir . '/composer.json';

$mergedComposer = [
    'name' => 'my-framework/plugin-bundle',
    'description' => 'Merged plugin dependencies',
    'require' => [],
    'autoload' => [
        'psr-4' => []
    ]
];

// Find all plugin subdirectories
$pluginPaths = glob($pluginDir . '/*', GLOB_ONLYDIR);

// Process each composer.json found in plugin folders
foreach ($pluginPaths as $pluginPath) {
    $composerFile = $pluginPath . '/composer.json';

    if (!file_exists($composerFile)) {
        continue;
    }

    $json = json_decode(file_get_contents($composerFile), true);
    if (!$json) {
        echo "⚠️  Invalid composer.json in $composerFile, skipping." . PHP_EOL;
        continue;
    }

    // Check for _base3.merge === true
    if (empty($json['_base3']['merge'])) {
        echo "⏭️  Skipping (no _base3.merge flag): $composerFile" . PHP_EOL;
        continue;
    }

    echo "🔄 Merging: $composerFile" . PHP_EOL;

    // ✅ Merge "require" section
    if (!empty($json['require'])) {
        foreach ($json['require'] as $package => $version) {
            if (isset($mergedComposer['require'][$package]) && $mergedComposer['require'][$package] !== $version) {
                echo "⚠️  Conflict for package $package: '{$mergedComposer['require'][$package]}' vs '$version'" . PHP_EOL;
                // TODO: Resolve conflicts if necessary
            }
            $mergedComposer['require'][$package] = $version;
        }
    }

    // ✅ Merge PSR-4 autoload section
    if (!empty($json['autoload']['psr-4'])) {
        foreach ($json['autoload']['psr-4'] as $namespace => $path) {
            $relativePluginPath = 'plugin/' . basename($pluginPath) . '/' . rtrim($path, '/');
            $mergedComposer['autoload']['psr-4'][$namespace] = $relativePluginPath . '/';
        }
    }
}

// Stop if no content was merged
if (empty($mergedComposer['require']) && empty($mergedComposer['autoload']['psr-4'])) {
    echo "⚠️  No eligible plugin composer.json files found. Skipping composer.json creation." . PHP_EOL;
    exit(0);
}

// 🔠 Sort merged sections for readability
ksort($mergedComposer['require']);
ksort($mergedComposer['autoload']['psr-4']);

// Ensure empty PSR-4 block is treated as an object in JSON
if (empty($mergedComposer['autoload']['psr-4'])) {
    $mergedComposer['autoload']['psr-4'] = new stdClass();
}

// ✍️ Write the merged composer.json file
file_put_contents($outputFile, json_encode($mergedComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "✅ Merged composer.json written to: $outputFile" . PHP_EOL;

