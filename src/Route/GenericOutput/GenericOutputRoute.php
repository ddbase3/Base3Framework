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

        // Sprachcode + Name
        if (preg_match('#^(?P<data>[a-z]{2})/(?P<name>[^/\.]+)\.(?P<out>php|html|json|xml|help)$#i', $path, $m)) {
            $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $m['name']);
            if (is_object($instance)) {
                return ['data' => $m['data'], 'name' => $m['name'], 'out' => $m['out']];
            }
            return null;
        }

        // Nur Name
        if (preg_match('#^(?P<name>[^/\.]+)\.(?P<out>php|html|json|xml|help)$#i', $path, $m)) {
            $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $m['name']);
            if (is_object($instance)) {
                return ['data' => '', 'name' => $m['name'], 'out' => $m['out']];
            }
            return null;
        }

        // Root oder index.php
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
        $data = $match['data'] ?? '';

        if ($out === 'php') {
            $out = 'html';
        }

        $_GET['name'] = $name;
        $_REQUEST['name'] = $name;

        // Sprache weitergeben
        if ($this->language && $data !== '' && strlen($data) === 2 && method_exists($this->language, 'setLanguage')) {
            $this->language->setLanguage($data);
        }

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

        return (string)$instance->getOutput($out);
    }
}

