<?php

use Adapterman\Adapterman;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/../../vendor/autoload.php';

Adapterman::init();

$worker = new Worker('http://0.0.0.0:18080');
$worker->name  = "Adapterman Tests";
//$worker->count = 2;

$worker->onMessage = static function (TcpConnection $connection): void {

    $response = match (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
        '/'          => 'Hello World!',
        '/get'       => json_encode($_GET),
        '/post'      => json_encode($_POST),
        '/headers'   => json_encode(getallheaders()),
        '/method'    => $_SERVER['REQUEST_METHOD'],
        '/server_ip' => $_SERVER['SERVER_ADDR'],
        '/ip'        => $_SERVER['REMOTE_ADDR'],
        '/cookies'   => cookies(),
        '/upload'    => json_encode($_FILES),

        default => (static function (): string {
            http_response_code(404);
            return '404 Not Found';
        })(),
    };

    $connection->send($response);
};

Worker::runAll();

function cookies(): string
{
    if($_GET === []) {
        return json_encode($_COOKIE);
    }

    if(isset($_GET['set'])) {
        foreach($_GET['set'] as $name => $value) {
            setcookie($name, $value);
        }
        return json_encode($_COOKIE);
    }

    if(isset($_GET['delete'])) {
        foreach($_GET['delete'] as $name) {
            if (isset($_COOKIE[$name])) {
                unset($_COOKIE[$name]); 
                setcookie($name, '', -1);
            }
        }
        return json_encode($_COOKIE);
    }

}


