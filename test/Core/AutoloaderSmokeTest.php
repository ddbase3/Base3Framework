<?php

declare(strict_types=1);

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use Base3\Core\Autoloader;

class AutoloaderSmokeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Autoloader registrieren
        Autoloader::register();

        // Dynamische Plugins zum Autoloader hinzufügen
        self::addPlugins();
    }

    /**
     * Plugins dynamisch hinzufügen
     */
    private static function addPlugins(): void
    {
        // Dynamisch alle Plugins hinzufügen
        foreach (glob(DIR_PLUGIN . '*', GLOB_ONLYDIR) as $pluginPath) {
            $pluginName = basename($pluginPath);
            $srcPath = realpath($pluginPath . '/src');
            $testPath = realpath($pluginPath . '/test');

            if ($srcPath !== false) {
                // Füge das Plugin zum Autoloader hinzu
                echo "Plugin '$pluginName' hinzugefügt (src): $srcPath\n";
                Autoloader::registerPlugin($pluginName, $srcPath . '/');
            }

            if ($testPath !== false) {
                echo "Plugin '$pluginName' hinzugefügt (test): $testPath\n";
                Autoloader::registerPlugin($pluginName . '\\Test', $testPath . '/');
            }
        }
    }

    public function testAutoloaderLoadsDummyClass(): void
    {
        $dummy = new \Base3\Test\Dummy\DummyClass();
        $this->assertSame('Hello from DummyClass!', $dummy->sayHello());
    }
}

