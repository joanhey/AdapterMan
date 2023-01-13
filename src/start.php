<?php
use Adapterman\Adapterman;
use Adapterman\Http;
use Workerman\Worker;
use Workerman\Timer;

require_once __DIR__ . '/../../../../vendor/autoload.php';

Adapterman::init();

require __DIR__ . '/frameworks/index.php';

$http_worker = new Worker('http://0.0.0.0:8080');
$http_worker->count = cpu_count() * 4;
$http_worker->name = 'AdapterMan';

$http_worker->onWorkerStart = function (Worker $worker) {
    if ($worker->id === 0) {
        Timer::add(600, function(){
            Http::tryGcSessions();
        });
    }
};

$http_worker->onMessage = static function ($connection, $request) {
    $connection->send(run());
};

Worker::runAll();
