<?php

declare(strict_types=1);

/**
 * Ensures Laravel writable storage paths exist (Blade compiles to storage/framework/views).
 * Loaded from public/index.php, artisan, bootstrap/app.php, AppServiceProvider, and Composer.
 */
$base = dirname(__DIR__);

$dirs = [
    $base.'/storage',
    $base.'/storage/framework',
    $base.'/storage/framework/cache',
    $base.'/storage/framework/cache/data',
    $base.'/storage/framework/sessions',
    $base.'/storage/framework/testing',
    $base.'/storage/framework/views',
    $base.'/storage/logs',
    $base.'/storage/app/private',
    $base.'/storage/app/private/uploads',
    $base.'/storage/app/private/uploads/courseMonitoring',
    $base.'/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (file_exists($dir) && ! is_dir($dir)) {
        @unlink($dir);
    }
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (is_dir($dir)) {
        @chmod($dir, 0775);
    }
}
