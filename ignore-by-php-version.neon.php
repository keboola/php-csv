<?php

declare(strict_types=1);

use PHPStan\DependencyInjection\NeonAdapter;

$adapter = new NeonAdapter();

$config = [];
if (PHP_VERSION_ID >= 80000) {
    $config = array_merge_recursive($config, $adapter->load(__DIR__ . '/phpstan-baseline-8+.neon'));
} else {
    $config = array_merge_recursive($config, $adapter->load(__DIR__ . '/phpstan-baseline.neon'));
}

$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;