<?php

define('DIR_ROOT', __DIR__ . '/../');
define('DIR_SRC', DIR_ROOT . 'src/');
define('DIR_PLUGIN', DIR_ROOT . 'plugin/');

require DIR_SRC . 'Core/Autoloader.php';
\Base3\Core\Autoloader::register();

putenv('DEBUG=1');

