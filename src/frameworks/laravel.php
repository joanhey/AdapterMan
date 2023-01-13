<?php

$app = require_once __DIR__.'/../../../../../bootstrap/app.php';

global $kernel;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function run()
{
    global $kernel;

    ob_start();

    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);

    return ob_get_clean();
}
