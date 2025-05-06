<?php

/**
 * Merges all plugin composer.json files into a single composer.json
 * located at: plugin/composer.json
 */

$rootDir = dirname(__DIR__);
$pluginDir = $rootDir . '/plugin';
$outputFile = $pluginDir . '/composer.json';

$mergedComposer = [
    'name' => 'my-framework/plugin-bundle',
    'description' => 'Merged plugin dependencies',
    'require' => [],
    'autoload' => [
        'psr-4' => []
    ]
];

// Hole alle Plugin-Unterverzeichnisse
$pluginPaths = glob($pluginDir . '/*', GLOB_ONLYDIR);

// Verarbeitung jeder composer.json im Plugin-Verzeichnis
foreach ($pluginPaths as $pluginPath) {
    $composerFile = $pluginPath . '/composer.json';

    if (!file_exists($composerFile)) continue;

    echo "🔄 Merging: " . $composerFile . PHP_EOL;

    $json = json_decode(file_get_contents($composerFile), true);
    if (!$json) {
        echo "⚠️  Invalid composer.json in $composerFile, skipping." . PHP_EOL;
        continue;
    }

    // ✅ Merge "require"
    if (!empty($json['require'])) {
        foreach ($json['require'] as $package => $version) {
            if (isset($mergedComposer['require'][$package]) && $mergedComposer['require'][$package] !== $version) {
                echo "⚠️  Conflict for package $package: '{$mergedComposer['require'][$package]}' vs '$version'" . PHP_EOL;
                // Du kannst hier Logik ergänzen, um Versionen z. B. zu kombinieren
            }
            $mergedComposer['require'][$package] = $version;
        }
    }

    // ✅ Merge PSR-4 Autoload
    if (!empty($json['autoload']['psr-4'])) {
        foreach ($json['autoload']['psr-4'] as $namespace => $path) {
            // Berechne relativen Pfad zum Plugin-Unterordner
            $relativePluginPath = 'plugin/' . basename($pluginPath) . '/' . rtrim($path, '/');
            $mergedComposer['autoload']['psr-4'][$namespace] = $relativePluginPath . '/';
        }
    }
}

// 🔠 Sortieren für Übersichtlichkeit
ksort($mergedComposer['require']);
ksort($mergedComposer['autoload']['psr-4']);

// 👉 Falls keine PSR-4-Angaben vorhanden sind, ersetze Array durch leeres Objekt
if (empty($mergedComposer['autoload']['psr-4'])) {
    $mergedComposer['autoload']['psr-4'] = new stdClass();
}

// ✍️ Schreiben der zusammengeführten composer.json
file_put_contents($outputFile, json_encode($mergedComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "✅ Merged composer.json written to: $outputFile" . PHP_EOL;

