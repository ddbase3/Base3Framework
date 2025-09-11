<?php declare(strict_types=1);

namespace Base3\Route\GenericOutput;

use Base3\Route\Api\IRoute;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Page\Api\IPageCatchall;

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

        if (preg_match('#^(?P<data>[^/]+)/(?P<name>[^/\.]+)\.(?P<out>php|html|json|xml)$#i', $path, $m)) {
            return ['data' => $m['data'], 'name' => $m['name'], 'out' => $m['out']];
        }
        if (preg_match('#^(?P<name>[^/\.]+)\.(?P<out>php|html|json|xml)$#i', $path, $m)) {
            return ['data' => '', 'name' => $m['name'], 'out' => $m['out']];
        }
        if ($path === '' || $path === 'index.php') {
            return ['data' => '', 'name' => 'index', 'out' => 'php'];
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

        // Für Kompatibilität alte $_REQUEST-Parameter setzen
        $_GET['name'] = $name;
        $_REQUEST['name'] = $name;

        if ($this->language && $data !== '' && strlen($data) === 2 && method_exists($this->language, 'setLanguage')) {
            $this->language->setLanguage($data);
        }

        $base = $this->config->get('base');
        if (!empty($this->accesscontrol->getUserId()) && !empty($base['intern'] ?? '') && $name === 'index') {
            header('Location: ' . ($base['url'] ?? '/') . $base['intern']);
            exit;
        }

        $instance = $this->classmap->getInstanceByInterfaceName(IOutput::class, $name);
        if (!is_object($instance)) {
            $catchalls = $this->classmap->getInstancesByInterface(IPageCatchall::class);
            $instance = reset($catchalls) ?: null;
            if (!is_object($instance)) {
                header('HTTP/1.0 404 Not Found');
                return "404 Not Found\n";
            }
        }

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

