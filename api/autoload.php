<?php
/**
 * Simple project autoloader
 * PSR-4 like (limited) for folders: core, models, repositories, services, controllers
 */

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    $paths = [
        'core', 'models', 'repositories', 'services', 'controllers'
    ];

    foreach ($paths as $p) {
        $file = $baseDir . $p . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
