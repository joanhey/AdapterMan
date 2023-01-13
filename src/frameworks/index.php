<?php

$baseDir = __DIR__ . '/../../../../../';

if (is_file("$baseDir/artisan")) {
    if (!class_exists(Laravel\Lumen\Application::class)) {
        include __DIR__ . '/laravel.php';
        return;
    } else {
        include __DIR__ . '/lumen.php';
        return;
    }
}
if (is_file("$baseDir/think")) {
    include __DIR__ . '/think.php';
    return;
}

exit("Unable to detect which framework is used\n");
