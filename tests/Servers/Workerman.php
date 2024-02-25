<?php

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;
use Workerman\Timer;
use Workerman\Worker;

require __DIR__.'/../../vendor/autoload.php';

$worker = new Worker('http://0.0.0.0:8080');
$worker->name = 'Workerman Tests';

$worker->onMessage = function (TcpConnection $connection, Request $request) {
    match ($request->path()) {
        '/' => $connection->send('Hello World!'),
        '/get' => $connection->send(json_encode($request->get())),
        '/post' => $connection->send(json_encode($request->post())),
        '/headers' => $connection->send(json_encode($request->header())),
        '/method' => $connection->send($request->method()),
        '/cookies' => $connection->send(cookies($request), true),
        '/ip' => $connection->send($connection->getLocalIp()),
        '/server_ip' => $connection->send($connection->getRemoteIp()),
        '/setSession' => (function () use ($connection, $request) {
            $request->session()->set('foo', 'bar');
            $connection->send('');
        })(),
        '/session' => $connection->send(cookies($request)),
        '/sse' => (function () use ($connection) {
            $connection->send(new Response(200, ['Content-Type' => 'text/event-stream'], "\r\n"));
            $i = 0;
            $timer_id = Timer::add(0.001, function () use ($connection, &$timer_id, &$i) {
                if ($connection->getStatus() !== TcpConnection::STATUS_ESTABLISHED) {
                    Timer::del($timer_id);

                    return;
                }
                if ($i >= 5) {
                    Timer::del($timer_id);
                    $connection->close();

                    return;
                }
                $i++;
                $connection->send(new ServerSentEvents(['data' => "hello$i"]));
            });
        })(),
        '/upload' => $connection->send(json_encode($request->file())),
        '/file' => $connection->send(json_encode($request->file('file'))),
        default => $connection->send(new Response(404, [], '404 Not Found'))
    };
};

Worker::runAll();

function cookies(Request $request): string
{
    if ($request->get() === []) {
        return encode($request->cookie());
    }

    $response = new Response(headers: ['Content-Type' => 'application/json']);
    if ($set = $request->get('set')) {
        foreach ($set as $name => $value) {
            $response->cookie($name, $value);
        }

        return $response->withBody(json_encode($request->cookie()));
    }

    if ($delete = $request->get('delete')) {
        foreach ($delete as $name) {
            if ($request->cookie($name)) {
                //unset($_COOKIE[$name]);
                $cookies = $request->cookie();
                unset($cookies[$name]);
                $response->cookie($name, '', -1);
            }
        }

        return $response->withBody(json_encode($cookies));
    }
}
