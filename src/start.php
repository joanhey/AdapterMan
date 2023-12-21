<?php

use Adapterman\Adapterman;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/frameworks/index.php';

$httpWorker = Adapterman::initWorker(
    socketName: 'http://0.0.0.0:8080',
    processName: 'AdapterMan',
    workersCount: cpu_count() * 4,
    sessionTTL: 599,
    onMessage: static function ($connection) {
        $connection->send(run());
    }
);

$httpWorker::runAll();
