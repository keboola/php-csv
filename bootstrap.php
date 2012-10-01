<?php

ini_set('display_errors', true);


spl_autoload_register(function ($className) {
    $file = realpath(
        __DIR__ . DIRECTORY_SEPARATOR
        . 'src' . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php'
    );

    if (!file_exists($file)) {
        throw new \InvalidArgumentException('Could not load class: '.$className);
    }

    require_once $file;
});
