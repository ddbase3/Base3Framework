<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;

// TODO test, still experimental
class ClassMapComposer extends AbstractClassMap {

    private $classmap;

    public function __construct(IContainer $container) {
        $this->container = $container;
        $this->filename = DIR_TMP . 'classmap.php';
        $this->generate();
        $this->map = require $this->filename;
    }

    public function generate($regenerate = false) {
        if (!$regenerate && file_exists($this->filename)) return;

        if (!is_writable(DIR_TMP)) {
            die('Directory /tmp has to be writable.');
        }

        // TODO configure location of autoload classmap file
        $this->classmap = require dirname(__DIR__, 3) . '/vendor/composer/autoload_classmap.php';
        $this->map = [];

        foreach ($this->classmap as $class => $file) {
            if (!class_exists($class, false)) {
                require_once $file;
            }

            if (!class_exists($class, false)) continue;

            $rc = new \ReflectionClass($class);
            if ($rc->isAbstract()) continue;

            $interfaces = $rc->getInterfaceNames();

            // TODO remove
            // if (strpos($class, 'Base3\\') !== 0) continue;

            $parts = explode("\\", $class);
            // TODO remove
            // if (count($parts) < 3) continue;
            $app = $parts[1];

            foreach ($interfaces as $interface) {
                $this->map[$app]["interface"][$interface][] = $class;

                if ($interface === \Base3\Api\IBase::class) {
                    $instance = $this->instantiate($class);
                    if ($instance && method_exists($instance, 'getName')) {
                        $name = $instance->getName();
                        $this->map[$app]["name"][$name] = $class;
                    }
                }
            }
        }

        $str = "<?php return " . var_export($this->map, true) . ";\n";
        file_put_contents($this->filename, $str);
    }
}

