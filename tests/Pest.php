<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/
use Tests\ServerTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Utils;

uses(ServerTestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function HttpClient(): Client
{
    return new Client([
        'base_uri' => 'http://127.0.0.1:8080',
        'headers' => [
            'User-Agent' => 'Testing/1.0',
        ],
        'cookies' => true,
        'http_errors' => false,
    ]);
}

/**
 * Send a raw HTTP/1.1 message and read the full response (uses Content-Length when present).
 */
function rawTcpHttpExchange(string $payload): string
{
    $fp = @stream_socket_client('tcp://127.0.0.1:8080', $errno, $errstr, 5);
    if ($fp === false) {
        throw new \RuntimeException("Failed to connect to test server: $errstr ($errno)");
    }
    stream_set_timeout($fp, 3);
    stream_set_blocking($fp, true);

    fwrite($fp, $payload);

    $response = '';
    while (!feof($fp)) {
        $chunk = fread($fp, 8192);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $response .= $chunk;

        $hdrEnd = strpos($response, "\r\n\r\n");
        if ($hdrEnd === false) {
            continue;
        }

        $headers = substr($response, 0, $hdrEnd);
        if (preg_match('/Content-Length:\s*(\d+)/i', $headers, $m)) {
            $bodyLen = (int) $m[1];
            $need = $hdrEnd + 4 + $bodyLen;
            if (strlen($response) >= $need) {
                break;
            }
        } else {
            break;
        }

        $meta = stream_get_meta_data($fp);
        if (!empty($meta['timed_out'])) {
            break;
        }
    }

    fclose($fp);

    return $response;
}

/**
 * Send a raw HTTP/1.1 request line (no libcurl normalization). Used to assert strict method tokens.
 *
 * Reads until the full message is received (per Content-Length) so we do not block on EOF when the
 * server keeps the connection open after sending 400.
 */
function rawTcpHttpRequest(string $method, string $path = '/method'): string
{
    $payload = "{$method} {$path} HTTP/1.1\r\nHost: 127.0.0.1:8080\r\nConnection: close\r\n\r\n";

    return rawTcpHttpExchange($payload);
}

/**
 * Build an HTTP/1.1 chunked payload (final zero chunk and empty trailers included).
 *
 * @param  array<int, string>  $dataChunks
 */
function httpChunkedEncode(array $dataChunks): string
{
    $out = '';
    foreach ($dataChunks as $chunk) {
        $out .= dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n";
    }

    return $out . "0\r\n\r\n";
}

function httpResponseStatus(string $raw): int
{
    if (preg_match('#^HTTP/1\.\d (\d+)#', $raw, $m)) {
        return (int) $m[1];
    }

    return 0;
}

function httpResponseBody(string $raw): string
{
    $p = strpos($raw, "\r\n\r\n");

    return $p === false ? '' : substr($raw, $p + 4);
}

/**
 * True when a lowercase standard method is accepted on the wire (e.g. native Workerman 5+).
 * Adapterman still rejects non-uppercase tokens in Http::input(), so this stays false there.
 * Cached so we only probe once per process (use from skip() after the test server is up).
 */
function httpTestServerAcceptsLowercaseStandardMethodOnWire(): bool
{
    static $accepts = null;
    if ($accepts !== null) {
        return $accepts;
    }

    $raw = rawTcpHttpRequest('get', '/method');
    [$headerBlock] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    $accepts = str_starts_with($headerBlock, 'HTTP/1.1 200');

    return $accepts;
}
