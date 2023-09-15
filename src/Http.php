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

namespace Adapterman;

use Workerman\Connection\TcpConnection;
//use Workerman\Timer;
use Adapterman\HttpStatusCodes as Status;
/**
 * http protocol
 */
class Http
{
    use ParseMultipart, Session;

    /**
     * Http status.
     */
    public static string $status = '';

    /**
     * Headers.
     */
    public static array $headers = [];

    /**
     * Cookies.
     */
    public static array $cookies = [];

    /**
     * Cache.
     */
    protected static array $cache = [];

    /**
     * Phrases.
     *
     * @var array<int,string>
     *
     * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     */
    const CODES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', // WebDAV; RFC 2518
        103 => 'Early Hints', // RFC 8297

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // since HTTP/1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content', // RFC 7233
        207 => 'Multi-Status', // WebDAV; RFC 4918
        208 => 'Already Reported', // WebDAV; RFC 5842
        226 => 'IM Used', // RFC 3229

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // Previously "Moved temporarily"
        303 => 'See Other', // since HTTP/1.1
        304 => 'Not Modified', // RFC 7232
        305 => 'Use Proxy', // since HTTP/1.1
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect', // since HTTP/1.1
        308 => 'Permanent Redirect', // RFC 7538

        400 => 'Bad Request',
        401 => 'Unauthorized', // RFC 7235
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required', // RFC 7235
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed', // RFC 7232
        413 => 'Payload Too Large', // RFC 7231
        414 => 'URI Too Long', // RFC 7231
        415 => 'Unsupported Media Type', // RFC 7231
        416 => 'Range Not Satisfiable', // RFC 7233
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324, RFC 7168
        421 => 'Misdirected Request', // RFC 7540
        422 => 'Unprocessable Entity', // WebDAV; RFC 4918
        423 => 'Locked', // WebDAV; RFC 4918
        424 => 'Failed Dependency', // WebDAV; RFC 4918
        425 => 'Too Early', // RFC 8470
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        451 => 'Unavailable For Legal Reasons', // RFC 7725

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', // RFC 2295
        507 => 'Insufficient Storage', // WebDAV; RFC 4918
        508 => 'Loop Detected', // WebDAV; RFC 5842
        510 => 'Not Extended', // RFC 2774
        511 => 'Network Authentication Required', // RFC 6585
    ];

    public static function init(): void
    {
        static::sessionInit();
        //static::uploadInit();
    }

    /**
     * Reset.
     *
     */
    public static function reset(): void
    {
        static::$status = 'HTTP/1.1 200 OK';
        static::$headers = [
            'Content-Type' => 'Content-Type: text/html;charset=utf-8',
            'Server' => 'Server: workerman',
        ];
        static::$cookies = [];
        static::$sessionFile = '';
        static::$sessionStarted = false; 
    }

    /**
     * The supported HTTP methods
     *
     * @var string[]
     */
    const AVAILABLE_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

    /**
     * Send a raw HTTP header.
     */
    public static function header(string $content, bool $replace = true, int $http_response_code = 0): void
    {
        if (\str_starts_with($content, 'HTTP')) {
            static::$status = $content;

            return;
        }

        $key = \strstr($content, ':', true);
        if (empty($key)) {
            return;
        }

        if ('location' === \strtolower($key)) {
            if ($http_response_code === 0) {
                $http_response_code = 302;
            }
            static::responseCode($http_response_code);
        }

        if ($key === 'Set-Cookie') {
            static::$cookies[] = $content;
        } else {
            static::$headers[$key] = $content;
        }
    }

    /**
     * Remove previously set headers.
     *
     */
    public static function headerRemove(?string $name = null): void
    {
        if ($name === null) {
            static::$headers = [];
            static::$cookies = [];

            return;
        }

        unset(static::$headers[$name]);
    }

    /**
     * Sets the HTTP response status code.
     *
     * @param  int  $code The response code
     * @return bool|int The valid status code or FALSE if code is not provided and it is not invoked in a web server environment
     */
    public static function responseCode(int $code): bool|int
    {
        if (isset(static::CODES[$code])) {
            static::$status = "HTTP/1.1 $code " . static::CODES[$code];

            return $code;
        }

        return false;
    }

    /**
     * Set cookie.
     * 
     * @see https://www.php.net/manual/en/function.setcookie
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie
     * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md
     */
    public static function setCookie(
        string $name,
        string $value = '',
        int    $expires = 0,
        string $path = '',
        string $domain = '',
        bool   $secure = false,
        bool   $httponly = false,
        string $samesite = '',
    ): bool
    {
        if (! static::checkCookieSamesite($samesite)) {
            $samesite = '';
        }

        static::$cookies[] = 'Set-Cookie: ' . $name . '=' . \rawurlencode($value)
            . ($domain   ? '; Domain=' . $domain : '')
            . (($expires === 0) ? '' : '; Max-Age=' . $expires)
            . ($path     ? '; Path=' . $path : '')
            . ($secure   ? '; Secure' : '')
            . ($httponly ? '; HttpOnly' : '')
            . ($samesite ? "; SameSite=$samesite" : '');

        return true;
    }

    // TODO: add setrawcookie

    protected static function checkCookieSamesite(string $samesite): bool
    {
        $valid = ['None', 'Lax', 'Strict'];

        if(\in_array($samesite, $valid)) {
            return true;
        }

        return false;
    }

    /**
     * Returns a list of response headers sent (or ready to send)
     *
     * @return array<string>
     */
    public static function headers_list(): array
    {
        return [...static::$cookies, ...static::$headers];
    }

    /**
     * Check the integrity of the package.
     */
    public static function input(string $recv_buffer, TcpConnection $connection): int
    {
        if (isset(static::$cache[$recv_buffer]['input'])) {
            return static::$cache[$recv_buffer]['input'];
        }
        $recv_len = \strlen($recv_buffer);
        $crlf_post = \strpos($recv_buffer, "\r\n\r\n");
        if (!$crlf_post) {
            // Judge whether the package length exceeds the limit.
            if ($recv_len >= $connection->maxPackageSize) {
                $connection->close();
            }

            return 0;
        }
        $head_len = $crlf_post + 4;

        $method = \substr($recv_buffer, 0, \strpos($recv_buffer, ' '));
        if (!\in_array($method, static::AVAILABLE_METHODS)) {
            $connection->send("HTTP/1.1 400 Bad Request\r\nContent-Length: 0\r\n\r\n", true);
            $connection->consumeRecvBuffer($recv_len);

            return 0;
        }

        if ($method === 'GET' || $method === 'OPTIONS' || $method === 'HEAD') {
            static::$cache[$recv_buffer]['input'] = $head_len;

            return $head_len;
        }

        $match = [];
        if (\preg_match("/\r\nContent-Length: ?(\d+)/i", $recv_buffer, $match)) {
            $content_length = $match[1] ?? 0;
            $total_length = (int) $content_length + $head_len;
            if (!isset($recv_buffer[1024])) {
                static::$cache[$recv_buffer]['input'] = $total_length;
            }

            return $total_length;
        }

        return ($method === 'DELETE' || $method === 'PATCH') ? $head_len : 0;
    }

    /**
     * Parse $_POST、$_GET、$_COOKIE.
     */
    public static function decode(string $recv_buffer, TcpConnection $connection): void
    {
        static::reset();
        if (isset(static::$cache[$recv_buffer]['decode'])) {
            $cache = static::$cache[$recv_buffer]['decode'];
            $_SERVER  = $cache['server'];
            $_POST    = $cache['post'];
            $_GET     = $cache['get'];
            $_COOKIE  = $cache['cookie'];
            $_REQUEST = $cache['request'];
            $GLOBALS['HTTP_RAW_POST_DATA'] = $GLOBALS['HTTP_RAW_REQUEST_DATA'] = '';

            return;
        }
        // Init.
        $_POST = $_GET = $_COOKIE = $_REQUEST = $_SESSION = $_FILES = [];
        // $_SERVER
        $_SERVER = [
            'REQUEST_METHOD' => '',
            'REQUEST_URI' => '',
            'SERVER_PROTOCOL' => '',
            'SERVER_ADDR' => $connection->getLocalIp(),
            'SERVER_PORT' => $connection->getLocalPort(),
            'REMOTE_ADDR' => $connection->getRemoteIp(),
            'REMOTE_PORT' => $connection->getRemotePort(),
            'SERVER_SOFTWARE' => 'workerman',
            'SERVER_NAME' => '',
            'HTTP_HOST' => '',
            'HTTP_USER_AGENT' => '',
            'HTTP_ACCEPT' => '',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_COOKIE' => '',
            'HTTP_CONNECTION' => '',
            'CONTENT_TYPE' => '',
        ];

        // Parse headers.
        [$http_header, $http_body] = \explode("\r\n\r\n", $recv_buffer, 2);
        $header_data = \explode("\r\n", $http_header);

        [$_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']] = \explode(' ', $header_data[0]);

        $http_post_boundary = '';
        unset($header_data[0]);
        foreach ($header_data as $content) {
            // \r\n\r\n
            if (empty($content)) {
                continue;
            }
            [$key, $value] = \explode(':', $content, 2);
            $key = \str_replace('-', '_', \strtoupper($key));
            $value = \trim($value);
            $_SERVER['HTTP_' . $key] = $value;
            switch ($key) {
                    // HTTP_HOST
                case 'HOST':
                    $tmp = \explode(':', $value);
                    $_SERVER['SERVER_NAME'] = $tmp[0];
                    if (isset($tmp[1])) {
                        $_SERVER['SERVER_PORT'] = $tmp[1];
                    }
                    break;
                    // cookie
                case 'COOKIE':
                    \parse_str(\str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                    break;
                    // content-type
                case 'CONTENT_TYPE':
                    if (!\preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                        if ($pos = \strpos($value, ';')) {
                            $_SERVER['CONTENT_TYPE'] = \substr($value, 0, $pos);
                        } else {
                            $_SERVER['CONTENT_TYPE'] = $value;
                        }
                    } else {
                        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
                        $http_post_boundary = '--' . $match[1];
                    }
                    break;
                case 'CONTENT_LENGTH':
                    $_SERVER['CONTENT_LENGTH'] = $value;
                    break;
            }
        }

        // Parse $_POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE']) {
            match ($_SERVER['CONTENT_TYPE']) {
                'multipart/form-data' => static::parseMultipart($http_body, $http_post_boundary),
                'application/json' => $_POST = \json_decode($http_body, true) ?? [],
                'application/x-www-form-urlencoded' => \parse_str($http_body, $_POST),
                default => ''
            };
        }

        // Parse other HTTP action parameters
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $data = [];
            match ($_SERVER['CONTENT_TYPE']) {
                'application/x-www-form-urlencoded' => \parse_str($http_body, $data),
                'application/json' => $data = \json_decode($http_body, true) ?? [],
                default => ''
            };
            $_REQUEST = \array_merge($_REQUEST, $data);
        }

        // HTTP_RAW_REQUEST_DATA HTTP_RAW_POST_DATA
        $GLOBALS['HTTP_RAW_REQUEST_DATA'] = $GLOBALS['HTTP_RAW_POST_DATA'] = $http_body;

        // QUERY_STRING
        $_SERVER['QUERY_STRING'] = \parse_url($_SERVER['REQUEST_URI'], \PHP_URL_QUERY);
        if ($_SERVER['QUERY_STRING']) {
            // $GET
            \parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            $_SERVER['QUERY_STRING'] = '';
        }

        // REQUEST
        $_REQUEST = \array_merge($_GET, $_POST, $_REQUEST);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            static::$cache[$recv_buffer]['decode'] = [
                'get'    => $_GET,
                'post'   => $_POST,
                'cookie' => $_COOKIE,
                'server' => $_SERVER,
                'files'  => $_FILES,
                'request'=> $_REQUEST,
            ];
            if (\count(static::$cache) > 256) {
                unset(static::$cache[\key(static::$cache)]);
            }
        }
    }

    /**
     * Http encode.
     *
     * @param  string  $content
     */
    public static function encode(string $content, TcpConnection $connection): string
    {
        //$content = (string) $content;

        // http-code status line.
        $header = static::$status . "\r\n";

        // create headers
        if ($headers = self::headers_list()) {
            $header .= \implode("\r\n", $headers) . "\r\n";
        }

        if (!empty($connection->gzip)) {
            $header .= "Content-Encoding: gzip\r\n";
            $content = \gzencode($content, $connection->gzip);
        }
        // header
        $header .= 'Content-Length: ' . \strlen($content) . "\r\n\r\n";

        // save session
        static::sessionWriteClose();

        // the whole http package
        return $header . $content;
    }
}
