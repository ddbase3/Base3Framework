<?php declare(strict_types=1);

namespace Base3\Route\GenericOutput;

use Base3\Route\Api\IRoute;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;

final class GenericOutputRoute implements IRoute {
    public function __construct(
        private IClassMap $classmap,
        private IConfiguration $config,
        private IAccesscontrol $accesscontrol,
        private ?object $language = null
    ) {}

    public function match(string $path): ?array {
        $path = explode('?', $path, 2)[0];
        $path = ltrim($path, '/');

        $m = null;
        if (preg_match('#^(?P<name>[^/\.]+)\.(?P<out>php|html|json|xml|help)$#i', $path, $m)) {
            // prüfen, ob wirklich ein IOutput existiert
            $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $m['name']);
            if (is_object($instance)) {
                return ['data' => '', 'name' => $m['name'], 'out' => $m['out']];
            }
            return null; // kein IOutput → Router probiert nächste Route
        }

        if ($path === '' || $path === 'index.php') {
            $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, 'index');
            if (is_object($instance)) {
                return ['data' => '', 'name' => 'index', 'out' => 'php'];
            }
            return null;
        }

        return null;
    }

    public function dispatch(array $match): string {
        $name = $match['name'];
        $out  = $match['out'];

        if ($out === 'php') {
            $out = 'html';
        }

        $_GET['name'] = $name;
        $_REQUEST['name'] = $name;

        $base = $this->config->get('base');
        if (!empty($this->accesscontrol->getUserId()) && !empty($base['intern'] ?? '') && $name === 'index') {
            header('Location: ' . ($base['url'] ?? '/') . $base['intern']);
            exit;
        }

        $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $name);

        if ($out === 'help') {
            if (!getenv('DEBUG')) return '';
            return $instance->getHelp();
        }
        if ($out === 'json') {
            header('Content-Type: application/json');
        }
        if ($out === 'html') {
            header('Content-Type: text/html; charset=utf-8');
        }

        return (string) $instance->getOutput($out);
    }
}

