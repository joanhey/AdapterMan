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
     * Bad request (chunked / header validation).
     */
    private const HTTP_400 = "HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n";

    /**
     * Payload too large.
     */
    private const HTTP_413 = "HTTP/1.1 413 Payload Too Large\r\nConnection: close\r\n\r\n";

    /**
     * Send content in response
     * to not send with HEAD request or 204 and 304 response
     *
     * @var boolean
     */
    protected static bool $responseContent = true;

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
        static::$responseContent = true;
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
        $normalized = self::normalizeHeaderLine($content);
        if ($normalized === null) {
            return;
        }

        if (\strlen($normalized) >= 5 && \strncasecmp($normalized, 'HTTP/', 5) === 0) {
            static::$status = $normalized;

            return;
        }

        $key = \strstr($normalized, ':', true);
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
            static::$cookies[] = $normalized;
        } else {
            static::$headers[$key] = $normalized;
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
        return \in_array($samesite, ['None', 'Lax', 'Strict']);
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
        if ($crlf_post === false) {
            if ($recv_len >= 16384) {
                $connection->end(static::HTTP_413, true);
            }

            return 0;
        }
        $head_len = $crlf_post + 4;

        $sp = \strpos($recv_buffer, ' ');
        if ($sp === false) {
            $connection->end(static::HTTP_400, true);

            return 0;
        }
        $method = \substr($recv_buffer, 0, $sp);
        if (!\in_array($method, static::AVAILABLE_METHODS)) {
            $connection->send(static::HTTP_400, true);
            $connection->consumeRecvBuffer($recv_len);

            return 0;
        }

        $match = [];
        if (\preg_match("/\r\nContent-Length: ?(\d+)/i", $recv_buffer, $match)) {
            if (\preg_match('/\r\nTransfer-Encoding[ \t]*:/i', $recv_buffer)) {
                $connection->end(static::HTTP_400, true);

                return 0;
            }
            $content_length = $match[1] ?? 0;
            $total_length = (int) $content_length + $head_len;
            if ($total_length > $connection->maxPackageSize) {
                $connection->end(static::HTTP_413, true);

                return 0;
            }
            if (!isset($recv_buffer[1024])) {
                static::$cache[$recv_buffer]['input'] = $total_length;
            }

            return $total_length;
        }

        $header = isset($recv_buffer[$head_len]) ? \substr($recv_buffer, 0, $head_len) : $recv_buffer;
        if (\preg_match('~\r\nTransfer-Encoding[ \t]*:~i', $header)) {
            $chunkedLen = static::inputChunked($recv_buffer, $connection, $header, $head_len);
            if ($chunkedLen > 0) {
                $connection->context ??= new \stdClass();
                $connection->context->chunked = true;
            }

            return $chunkedLen;
        }

        if ($method === 'GET' || $method === 'OPTIONS' || $method === 'HEAD') {
            static::$cache[$recv_buffer]['input'] = $head_len;
            if ($method === 'HEAD') {
                static::$responseContent = false;
            }

            return $head_len;
        }

        return ($method === 'DELETE' || $method === 'PATCH') ? $head_len : 0;
    }

    /**
     * Check the integrity of a chunked transfer-encoded request.
     */
    protected static function inputChunked(string $buffer, TcpConnection $connection, string $header, int $headerLength): int
    {
        $pattern = '~\A'
            . '(?![\s\S]*\r\nContent-Length[ \t]*:)'
            . '(?![\s\S]*\r\nTransfer-Encoding[ \t]*:[\s\S]*\r\nTransfer-Encoding[ \t]*:)'
            . '(?=[\s\S]*\r\nTransfer-Encoding[ \t]*:[ \t]*chunked[ \t]*\r\n)'
            . '(?:GET|POST|OPTIONS|HEAD|DELETE|PUT|PATCH) +\/[^\x00-\x20\x7f]* +HTTP\/1\.[01]\r\n~i';

        if (!\preg_match($pattern, $header)) {
            $connection->end(static::HTTP_400, true);

            return 0;
        }

        $pos = $headerLength;
        $bufLen = \strlen($buffer);
        $maxSize = $connection->maxPackageSize;

        while (true) {
            $lineEnd = \strpos($buffer, "\r\n", $pos);
            if ($lineEnd === false) {
                return 0;
            }

            $semiPos = \strpos($buffer, ';', $pos);
            $hexEnd = ($semiPos !== false && $semiPos < $lineEnd) ? $semiPos : $lineEnd;
            $hexStr = \substr($buffer, $pos, $hexEnd - $pos);

            if ($hexStr === '' || !\ctype_xdigit($hexStr) || isset($hexStr[16])) {
                $connection->end(static::HTTP_400, true);

                return 0;
            }

            $chunkSize = \hexdec($hexStr);
            if (\is_float($chunkSize)) {
                $connection->end(static::HTTP_400, true);

                return 0;
            }
            $pos = $lineEnd + 2;

            if ($chunkSize === 0) {
                while (true) {
                    $lineEnd = \strpos($buffer, "\r\n", $pos);
                    if ($lineEnd === false) {
                        return 0;
                    }
                    if ($lineEnd === $pos) {
                        $totalLength = $pos + 2;
                        if ($totalLength > $maxSize) {
                            $connection->end(static::HTTP_413, true);

                            return 0;
                        }

                        return $totalLength;
                    }
                    $pos = $lineEnd + 2;
                }
            }

            if ($pos + $chunkSize + 2 > $bufLen) {
                return 0;
            }
            if (\substr($buffer, $pos + $chunkSize, 2) !== "\r\n") {
                $connection->end(static::HTTP_400, true);

                return 0;
            }
            $pos += $chunkSize + 2;

            if ($pos > $maxSize) {
                $connection->end(static::HTTP_413, true);

                return 0;
            }
        }
    }

    /**
     * Decode a chunked transfer-encoded request into a normalized buffer (Content-Length body).
     *
     * @return array{string, array<string, string>}
     */
    protected static function decodeChunked(string $buffer, int $headerEnd): array
    {
        $header = \preg_replace('~\r\nTransfer-Encoding[ \t]*:[^\r]*~i', '', \substr($buffer, 0, $headerEnd), 1);
        $body = '';
        $trailers = [];
        $pos = $headerEnd + 4;
        $bufLen = \strlen($buffer);

        while (true) {
            $lineEnd = \strpos($buffer, "\r\n", $pos);
            if ($lineEnd === false) {
                break;
            }

            $semiPos = \strpos($buffer, ';', $pos);
            $hexEnd = ($semiPos !== false && $semiPos < $lineEnd) ? $semiPos : $lineEnd;
            $hexStr = \substr($buffer, $pos, $hexEnd - $pos);
            if ($hexStr === '' || !\ctype_xdigit($hexStr) || isset($hexStr[16])) {
                break;
            }

            $chunkSize = \hexdec($hexStr);
            if (\is_float($chunkSize)) {
                break;
            }
            $pos = $lineEnd + 2;

            if ($chunkSize === 0) {
                while (true) {
                    $lineEnd = \strpos($buffer, "\r\n", $pos);
                    if ($lineEnd === false) {
                        break 2;
                    }
                    if ($lineEnd === $pos) {
                        $pos += 2;
                        break;
                    }
                    $colonPos = \strpos($buffer, ':', $pos);
                    if ($colonPos !== false && $colonPos < $lineEnd) {
                        $trailers[\strtolower(\substr($buffer, $pos, $colonPos - $pos))] = \ltrim(\substr($buffer, $colonPos + 1, $lineEnd - $colonPos - 1));
                    }
                    $pos = $lineEnd + 2;
                }
                break;
            }

            if ($pos + $chunkSize + 2 > $bufLen) {
                break;
            }
            if (\substr($buffer, $pos + $chunkSize, 2) !== "\r\n") {
                break;
            }
            $body .= \substr($buffer, $pos, $chunkSize);
            $pos += $chunkSize + 2;
        }

        return [$header . "\r\nContent-Length: " . \strlen($body) . "\r\n\r\n" . $body, $trailers];
    }

    /**
     * Strip trailing whitespace then reject headers still containing CR, LF, or NUL.
     *
     * @return non-empty-string|null
     */
    private static function normalizeHeaderLine(string $content): ?string
    {
        $line = \rtrim($content);
        if ($line === '' || \strpbrk($line, "\r\n\0") !== false) {
            return null;
        }

        return $line;
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
            $_FILES   = $cache['files'];
            $_REQUEST = $cache['request'];
            $GLOBALS['HTTP_RAW_POST_DATA'] = $GLOBALS['HTTP_RAW_REQUEST_DATA'] = '';

            return;
        }

        $trailers = [];
        $ctx = $connection->context ?? null;
        if ($ctx !== null && isset($ctx->chunked)) {
            unset($ctx->chunked);
            $headerEnd = \strpos($recv_buffer, "\r\n\r\n");
            if ($headerEnd !== false) {
                [$recv_buffer, $trailers] = static::decodeChunked($recv_buffer, $headerEnd);
            }
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
            'SERVER_SOFTWARE' => Adapterman::NAME,
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
                    $_SERVER['SERVER_PORT'] = (int) ($tmp[1] ?? 80);
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

        foreach ($trailers as $name => $value) {
            $key = \str_replace('-', '_', \strtoupper($name));
            $_SERVER['HTTP_' . $key] = $value;
        }

        // Parse $_POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE']) {
            match ($_SERVER['CONTENT_TYPE']) {
                'multipart/form-data' => static::parseMultipart($http_body, $http_post_boundary),
                'application/json' => $_POST = \json_decode($http_body, true, flags: \JSON_THROW_ON_ERROR) ?? [],
                'application/x-www-form-urlencoded' => \parse_str($http_body, $_POST),
                default => ''
            };
        }

        // Parse other HTTP action parameters
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $data = [];
            match ($_SERVER['CONTENT_TYPE']) {
                'application/x-www-form-urlencoded' => \parse_str($http_body, $data),
                'application/json' => $data = \json_decode($http_body, true, flags: \JSON_THROW_ON_ERROR) ?? [],
                default => ''
            };
            $_REQUEST = $data;
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
        $_REQUEST = [...$_GET, ...$_POST, ...$_REQUEST];

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

        if(!static::$responseContent) {
            $content = "";
        }

        // save session
        static::sessionWriteClose();

        // the whole http package
        return $header . $content;
    }
}
