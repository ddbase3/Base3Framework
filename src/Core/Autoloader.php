<?php declare(strict_types=1);

namespace Base3\Core;

class Autoloader
{
    /** @var array<string, string> Namespace-Prefixes → Verzeichnis-Mapping */
    private static array $prefixes = [];

    /** @var bool Autoloader bereits registriert? */
    private static bool $registered = false;

    private static string $dirSrc = "src/";
    private static string $dirTest = "test/";
    private static string $dirPlugin = "plugin/";

    /**
     * Registrierung des Autoloaders (nur einmal)
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Basisnamespaces
        self::addNamespace('Base3\\', self::$dirSrc);
        self::addNamespace('Base3\\Test\\', self::$dirTest);

        // Plugins dynamisch hinzufügen
        foreach (glob(self::$dirPlugin . '*', GLOB_ONLYDIR) as $pluginPath) {
            $pluginName = basename($pluginPath);
            self::addNamespace($pluginName . '\\', $pluginPath . '/src');
            self::addNamespace($pluginName . '\\Test\\', $pluginPath . '/test');
        }

        // Prefixes nach Länge sortieren (wichtig für überlappende Prefixes)
        uksort(self::$prefixes, fn($a, $b) => strlen($b) <=> strlen($a));

        spl_autoload_register([self::class, 'autoload']);
        self::$registered = true;
    }

    /**
     * Füge ein Namespace-Verzeichnis hinzu (falls vorhanden)
     */
    private static function addNamespace(string $prefix, string $dir): void
    {
        $path = realpath($dir);
        if ($path !== false) {
            self::$prefixes[$prefix] = rtrim($path, '/') . '/';
        }
    }

    public static function registerPlugin(string $pluginNamespace, string $baseDir): void
    {
        self::$prefixes[$pluginNamespace] = $baseDir;
    }

    /**
     * PSR-4 kompatibler Autoloader
     */
    private static function autoload(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                if (getenv('DEBUG') === '2') {
                    echo "Autoload: $class → $file\n";
                }

                if (file_exists($file)) {
                    require $file;
                }

                return;
            }
        }

        if (getenv('DEBUG') === '1') {
            echo "Autoload: $class → NOT FOUND\n";
        }
    }
}

