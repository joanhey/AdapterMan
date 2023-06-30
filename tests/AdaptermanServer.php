<?php

use Adapterman\Adapterman;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/../vendor/autoload.php';

Adapterman::init();

$worker = new Worker('http://0.0.0.0:8080');
$worker->name  = "Adapterman Tests";
$worker->count = 2;

$worker->onMessage = static function (TcpConnection $connection, $request) {
    match (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
        '/' => $connection->send('Hello World!'),
        '/get' => $connection->send(json_encode($_GET)),
        '/post' => $connection->send(json_encode($_POST)),
        '/headers' => $connection->send(json_encode(getallheaders())),
        '/method' => $connection->send($_SERVER['REQUEST_METHOD']),
        '/server_ip' => $connection->send($_SERVER['SERVER_ADDR']),
        '/ip' => $connection->send($_SERVER['REMOTE_ADDR']),
        '/file' => $connection->send(json_encode($request->file('file'))),

        default => (function () use ($connection) {
            http_response_code(404);
            $connection->send('404 Not Found');
        })(),
    };
};

Worker::runAll();

