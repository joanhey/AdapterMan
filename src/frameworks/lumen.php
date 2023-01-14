<?php
global $app;
$app = require_once __DIR__.'/../../../../../bootstrap/app.php';

function run()
{
    global $app;

    ob_start();

    $app->run();

    return ob_get_clean();
}

