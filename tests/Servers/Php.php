<?php
/**
 * This file is part of Adapterman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    Joan Miquel<https://github.com/joanhey>
 * @copyright Joan Miquel<https://github.com/joanhey>
 * @link      https://github.com/joanhey/AdapterMan
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */


// php -S 127.0.0.1:8080 tests/Servers/Php.php

// Parse JSON
if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
    $_POST = \json_decode(file_get_contents('php://input'), true) ?? [];
    file_put_contents("php://stdout", "\nRequested: $_POST");
}


// Router
$response = match (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    // Tests
    '/'          => 'Hello World!',
    '/get'       => encode($_GET),
    '/post'      => encode($_POST),
    '/headers'   => encode(getallheaders()),
    '/method'    => $_SERVER['REQUEST_METHOD'],
    '/server_ip' => $_SERVER['SERVER_ADDR'],
    '/ip'        => $_SERVER['REMOTE_ADDR'],
    '/cookies'   => cookies(),
    '/session'   => session(),
    '/session/destroy' => sessionDestroy(),
    '/upload'    => encode($_FILES),

    // Info for debug
    '/debug'      => debugLinks(),
    '/info'      => info(),
    '/globals'   => globals(),
    '/extensions'=> encode(get_loaded_extensions()),
    '/phpinfo'   => php_info(),
    '/getallheaders' => encode(getallheaders()),
    '/echo'      => requestEcho(),

    '/session/info' => SessionInfo(),

    default => (static function (): string {
        http_response_code(404);
        return '404 Not Found';
    })(),
};

echo $response;


function encode(mixed $data): string
{
    //so we can change it later
    header('Content-Type: application/json');
    return json_encode($data, JSON_PRETTY_PRINT);
}

function info(): string
{
    return encode([
        'PHP_VERSION'       => PHP_VERSION,
        'PHP_VERSION_ID'    => PHP_VERSION_ID,
        'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
        'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
        'PHP_SAPI'          => PHP_SAPI,
        'PHP_OS'            => PHP_OS,
        'PHP_OS_FAMILY'     => PHP_OS_FAMILY,

        //Manual change
        'PSR-7'             => false,
        'SERVER'            => 'cli-server',
        'FRAMEWORK'         => '',
        'FRAMEWORK_VERSION' => '',

        //'GLOBALS'           => $GLOBALS,
        //'PHP_CLI_PROCESS_TITLE' => PHP_CLI_PROCESS_TITLE,
    ]);
}

function globals(): string
{
    header('Content-Type: text/plain');
    ob_start();
    print_r($GLOBALS);
    return ob_get_clean();
}

function requestEcho(): string
{
    
    return encode([
        'headers' => getallheaders(),
        'body'    => file_get_contents('php://input'),
    ]);
}

function cookies(): string
{
    if($_GET === []) {
        return encode($_COOKIE);
    }

    if(isset($_GET['set'])) {
        foreach($_GET['set'] as $name => $value) {
            setcookie($name, $value);
        }
        return encode($_COOKIE);
    }

    if(isset($_GET['delete'])) {
        foreach($_GET['delete'] as $name) {
            if (isset($_COOKIE[$name])) {
                unset($_COOKIE[$name]); 
                setcookie($name, '', -1);
            }
        }
        return encode($_COOKIE);
    }

}

function sessionStatus(int $code): string
{
    $status = [ 
        PHP_SESSION_DISABLED => 'PHP_SESSION_DISABLED',
        PHP_SESSION_NONE     => 'PHP_SESSION_NONE',
        PHP_SESSION_ACTIVE   => 'PHP_SESSION_ACTIVE',
    ];

    return $status[$code];
}

function sessionInfo(): string
{
    return encode([
        'start'  => session_start(),
        'name'   => session_name(),
        'id'     => session_id(),
        'status' => sessionStatus(session_status()),
        'cookies'=> $_COOKIE,
        'default-cookie-params' => session_get_cookie_params(),
        'headers-to-send' => headers_list(),
        'data'   => $_SESSION,
    ]);
}

function sessionDestroy(): string
{
    session_start();
    
    return encode([
        'destroy'=> session_destroy(),
        'name'   => session_name(),
        'id'     => session_id(),
        'status' => sessionStatus(session_status()),
        'cookies'=> $_COOKIE,
        'default-cookie-params' => session_get_cookie_params(),
        'headers-to-send' => headers_list(),
        'data'   => $_SESSION,
    ]);
}

function session(): string
{
    session_start();

    if($_GET === []) {
        return encode($_SESSION);
    }

    if(isset($_GET['set'])) {
        foreach($_GET['set'] as $name => $value) {
            $_SESSION[$name] = $value;
        }
        //Worker::safeEcho(print_r($_SESSION));
        return encode($_SESSION);
    }

    if(isset($_GET['delete'])) {
        foreach($_GET['delete'] as $name) {
            if (isset($_SESSION[$name])) {
                unset($_SESSION[$name]); 
            }
        }
        return encode($_SESSION);
    }

}

function php_info(): string
{
    ob_start();
    phpinfo();
    return ob_get_clean();
}

function debugLinks(): string
{
    $output = <<<EOD
    <h1>Debug links</h1>
    <ul>
        <li><a href="/info">Info</a></li>
        <li><a href="/globals">GLOBALS</a></li>
        <li><a href="/extensions">Extensions loaded</a></li>
        <li><a href="/phpinfo">PHP info</a></li>
        <li><a href="/session/info">Session info</a></li>
        <li><a href="/session/destroy">Session destroy</a></li>
        <li><a href="/getallheaders">Get all request headers</a></li>
        <li><a href="/echo">Echo request</a></li>
    </ul>

    <p>Name tests v0.1</p>
    EOD;

    return $output;
}
