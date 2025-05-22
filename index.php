<?php declare(strict_types=1);

require __DIR__ . '/src/Core/Autoloader.php';
\Base3\Core\Autoloader::register();

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) require_once $composerAutoload;

require __DIR__ . '/src/Core/Bootstrap.php';
(new \Base3\Core\Bootstrap())->run();
