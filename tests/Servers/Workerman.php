<?php

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;
use Workerman\Timer;
use Workerman\Worker;

require __DIR__.'/../../vendor/autoload.php';

const TESTMAN_VERSION = '0.1';

$worker = new Worker('http://0.0.0.0:8080');
$worker->name = 'Workerman Tests';

$worker->onMessage = function (TcpConnection $connection, Request $request) {
    match ($request->path()) {
        '/'           => $connection->send('Hello World!'),
        '/get'        => $connection->send(encode($request->get())),
        '/post'       => $connection->send(encode($request->post())),
        '/headers'    => $connection->send(encode($request->header())),
        '/method'     => $connection->send($request->method()),
        '/cookies'    => $connection->send(cookies($request), true),
        '/ip'         => $connection->send($connection->getLocalIp()),
        '/server_ip'  => $connection->send($connection->getRemoteIp()),
        '/setSession' => (function () use ($connection, $request) {
            $request->session()->set('foo', 'bar');
            $connection->send('');
        })(),
        '/session'    => $connection->send($request->session()->pull('foo')),
        '/upload'     => $connection->send(encode($request->file())),
        //'/file'       => $connection->send(encode($request->file('file'))),

        // Workerman specific
        '/sse'        => sse($connection),

        // Info for debug
        '/debug'      => $connection->send(debugLinks()),
        '/info'       => $connection->send(info()),
        '/globals'    => $connection->send(globals()),
        '/extensions' => $connection->send(encode(get_loaded_extensions())),
        '/phpinfo'    => $connection->send(php_info()),
        '/getallheaders' => $connection->send(encode($request->header())),
        '/echo'       => $connection->send(requestEcho($request)),
        // to check also session path
        '/session/info' => $connection->send(sessionInfo()),


        default => $connection->send(new Response(404, [], '404 Not Found'))
    };
};

Worker::runAll();


function encode(mixed $data): Response
{
    //so we can change it later
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode($data, JSON_PRETTY_PRINT)
    );
}

// Only for info not for automatic tests
// mmm also to check ext, modules, before run some tests
function info(): Response
{
    return encode([
        'PHP_VERSION'       => PHP_VERSION,
        'PHP_VERSION_ID'    => PHP_VERSION_ID,
        'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
        'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
        'PHP_SAPI'          => PHP_SAPI,
        'PHP_OS'            => PHP_OS,
        'PHP_OS_FAMILY'     => PHP_OS_FAMILY,

        'TESTMAN_VERSION' => TESTMAN_VERSION,
        
        //Manual change
        'PSR-7'             => false,
        'SERVER'            => 'Workerman',
        'FRAMEWORK'         => 'Workerman4',
        'FRAMEWORk_VERSION'  => Worker::VERSION,

        //'GLOBALS'           => print_r($GLOBALS),
        //'PHP_CLI_PROCESS_TITLE' => PHP_CLI_PROCESS_TITLE,
    ]);
}

function globals(): Response
{
    ob_start();
    print_r($GLOBALS);

    return new Response(
        200,
        ['Content-Type' => 'text/plain'],
        ob_get_clean()
    );
}

function php_info(): Response
{
    ob_start();
    phpinfo();

    return new Response(
        200,
        ['Content-Type' => 'text/plain'],
        ob_get_clean()
    );
}

function requestEcho(Request $request): Response
{
    return encode([
        'headers' => $request->header(),
        'body'    => $request->rawBody(),
    ]);
}

function cookies(Request $request): string
{
    if ($request->get() === []) {
        return new Response(body: encode($request->cookie()));
    }

    $response = new Response();
    if ($set = $request->get('set')) {
        foreach ($set as $name => $value) {
            $response->cookie($name, $value);
        }

        return $response->withBody(encode($request->cookie()));
    }

    if ($delete = $request->get('delete')) {
        foreach ($delete as $name) {
            if ($request->cookie($name)) {
                //unset($_COOKIE[$name]);
                //$response->cookie($name, '', -1);
            }
        }

        return $response->withBody(encode($request->cookie()));
    }
}




function debugLinks(): string
{
    ob_start();
    print_r(json_decode(info()));
    $info = ob_get_clean();

    $TESMAN_VERSION = TESTMAN_VERSION;
    
    // TODO create a file with extra info and nicer
    $output = <<<EOD
    <h1>TestMan</h1>
    <pre>$info</pre>
    <h2>Debug links</h2>
    <ul>
        <li><a href="/info">Info</a></li>
        <li><a href="/globals">GLOBALS</a></li>
        <li><a href="/extensions">Extensions loaded</a></li>
        <li><a href="/phpinfo">PHP info</a></li>
        <li><a href="/session/info">Session info</a></li>
        <li><a href="/session/destroy">Session destroy</a></li>
        <li><a href="/getallheaders">Get all request headers</a></li>
        <li><a href="/echo">Echo request</a></li>
        <li><a href="/sse">Workerman SSE test</a></li>
    </ul>

    <p>TestMan v$TESMAN_VERSION</p>
    EOD;

    return $output;
}



/**
 * Test Server Side Events
 * Wokerman specific
 *
 */
function sse(TcpConnection $connection): void
{
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
        
}
