<?php

/**
 * BASE3 Composer merge
 *
 * - Uses composer.base.json as the stable, hand-maintained base file.
 * - Merges all plugin composer.json files that opt in via "_base3.merge": true
 *   into the generated root composer.json.
 *
 * Generated file: <project-root>/composer.json   (should be ignored / deleted by clean)
 * Base file:      <project-root>/composer.base.json (should be committed)
 */

$rootDir        = dirname(__DIR__, 2);
$pluginDir      = $rootDir . '/plugin';
$baseFile       = $rootDir . '/composer.base.json';
$outputFile     = $rootDir . '/composer.json';

function readJsonFile(string $file): array {
        $raw = file_get_contents($file);
        if ($raw === false) {
                throw new RuntimeException("Cannot read file: $file");
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
                throw new RuntimeException("Invalid JSON in file: $file");
        }

        return $json;
}

function ensureArrayPath(array &$arr, array $path): void {
        $ref = &$arr;
        foreach ($path as $key) {
                if (!isset($ref[$key]) || !is_array($ref[$key])) {
                        $ref[$key] = [];
                }
                $ref = &$ref[$key];
        }
}

function mergeRequire(array &$targetRequire, array $incomingRequire, string $source): void {
        foreach ($incomingRequire as $package => $version) {
                if (!isset($targetRequire[$package])) {
                        $targetRequire[$package] = $version;
                        continue;
                }

                if ($targetRequire[$package] !== $version) {
                        // Keep the existing constraint (base or earlier merge) and warn about conflicts
                        echo "⚠️  Conflict for package $package while merging $source: "
                                . "'{$targetRequire[$package]}' vs '$version' (keeping '{$targetRequire[$package]}')" . PHP_EOL;
                }
        }
}

function mergePsr4(array &$targetPsr4, array $incomingPsr4, string $pluginName, string $source): void {
        foreach ($incomingPsr4 as $namespace => $path) {
                $relative = 'plugin/' . $pluginName . '/' . rtrim((string)$path, '/') . '/';

                if (isset($targetPsr4[$namespace]) && $targetPsr4[$namespace] !== $relative) {
                        echo "⚠️  PSR-4 conflict for namespace $namespace while merging $source: "
                                . "'{$targetPsr4[$namespace]}' vs '$relative' (keeping '{$targetPsr4[$namespace]}')" . PHP_EOL;
                        continue;
                }

                $targetPsr4[$namespace] = $relative;
        }
}

try {
        // --- Load base composer file (required) ---
        if (!file_exists($baseFile)) {
                throw new RuntimeException("Missing base file: composer.base.json (expected at $baseFile)");
        }

        $mergedComposer = readJsonFile($baseFile);

        // Provide sane defaults if the base file doesn't define them
        if (empty($mergedComposer['name'])) {
                $mergedComposer['name'] = 'my-framework/base3-project';
        }
        if (empty($mergedComposer['description'])) {
                $mergedComposer['description'] = 'BASE3 project (generated composer.json)';
        }

        // Ensure expected sections exist
        ensureArrayPath($mergedComposer, ['require']);
        ensureArrayPath($mergedComposer, ['require-dev']);
        ensureArrayPath($mergedComposer, ['autoload', 'psr-4']);
        ensureArrayPath($mergedComposer, ['autoload-dev', 'psr-4']);

        $mergedAnyPlugin = false;

        // --- Find all plugin subdirectories ---
        $pluginPaths = glob($pluginDir . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($pluginPaths as $pluginPath) {
                $composerFile = $pluginPath . '/composer.json';
                if (!file_exists($composerFile)) {
                        continue;
                }

                $json = json_decode(file_get_contents($composerFile), true);
                if (!is_array($json)) {
                        echo "⚠️  Invalid composer.json in $composerFile, skipping." . PHP_EOL;
                        continue;
                }

                // Only merge plugins that opt in
                if (empty($json['_base3']['merge'])) {
                        echo "⏭️  Skipping (no _base3.merge flag): $composerFile" . PHP_EOL;
                        continue;
                }

                $pluginName = basename($pluginPath);
                echo "🔄 Merging: $composerFile" . PHP_EOL;
                $mergedAnyPlugin = true;

                // Merge require into root require
                if (!empty($json['require']) && is_array($json['require'])) {
                        mergeRequire($mergedComposer['require'], $json['require'], $composerFile);
                }

                // Merge require-dev into root require-dev (optional; only if the plugin wants to)
                if (!empty($json['require-dev']) && is_array($json['require-dev'])) {
                        mergeRequire($mergedComposer['require-dev'], $json['require-dev'], $composerFile);
                }

                // Merge autoload psr-4 into root autoload psr-4
                if (!empty($json['autoload']['psr-4']) && is_array($json['autoload']['psr-4'])) {
                        mergePsr4($mergedComposer['autoload']['psr-4'], $json['autoload']['psr-4'], $pluginName, $composerFile);
                }

                // Merge autoload-dev psr-4 into root autoload-dev psr-4 (optional; only if the plugin has tests)
                if (!empty($json['autoload-dev']['psr-4']) && is_array($json['autoload-dev']['psr-4'])) {
                        mergePsr4($mergedComposer['autoload-dev']['psr-4'], $json['autoload-dev']['psr-4'], $pluginName, $composerFile);
                }

                // Merge repositories (append roughly-unique entries)
                if (!empty($json['repositories']) && is_array($json['repositories'])) {
                        if (empty($mergedComposer['repositories']) || !is_array($mergedComposer['repositories'])) {
                                $mergedComposer['repositories'] = [];
                        }
                        // Naive append; Composer tolerates duplicates but we try to avoid exact duplicates
                        foreach ($json['repositories'] as $repo) {
                                if (!in_array($repo, $mergedComposer['repositories'], true)) {
                                        $mergedComposer['repositories'][] = $repo;
                                }
                        }
                }
        }

        // Sort for readability
        if (!empty($mergedComposer['require']) && is_array($mergedComposer['require'])) {
                ksort($mergedComposer['require']);
        }
        if (!empty($mergedComposer['require-dev']) && is_array($mergedComposer['require-dev'])) {
                ksort($mergedComposer['require-dev']);
        }
        if (!empty($mergedComposer['autoload']['psr-4']) && is_array($mergedComposer['autoload']['psr-4'])) {
                ksort($mergedComposer['autoload']['psr-4']);
        }
        if (!empty($mergedComposer['autoload-dev']['psr-4']) && is_array($mergedComposer['autoload-dev']['psr-4'])) {
                ksort($mergedComposer['autoload-dev']['psr-4']);
        }

        // Ensure empty require blocks are objects in JSON (Composer schema expects objects, not arrays)
        if (empty($mergedComposer['require'])) {
                $mergedComposer['require'] = new stdClass();
        }
        if (empty($mergedComposer['require-dev'])) {
                $mergedComposer['require-dev'] = new stdClass();
        }

        // Ensure empty PSR-4 blocks are objects in JSON (Composer style)
        if (empty($mergedComposer['autoload']['psr-4'])) {
                $mergedComposer['autoload']['psr-4'] = new stdClass();
        }
        if (empty($mergedComposer['autoload-dev']['psr-4'])) {
                $mergedComposer['autoload-dev']['psr-4'] = new stdClass();
        }

        file_put_contents(
                $outputFile,
                json_encode($mergedComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        if ($mergedAnyPlugin) {
                echo "✅ Generated composer.json (base + plugins) written to: $outputFile" . PHP_EOL;
        } else {
                echo "✅ Generated composer.json (base only; no eligible plugins) written to: $outputFile" . PHP_EOL;
        }

        exit(0);
} catch (Throwable $e) {
        fwrite(STDERR, "❌ merge-composer failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
}
