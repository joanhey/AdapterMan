<?php

/**
 * Integration tests for Transfer-Encoding: chunked (Adapterman\Http::inputChunked / decodeChunked).
 */

function chunkedRequest(string $method, string $path, string $chunkedBody, array $extraHeaders = []): string
{
    $lines = [
        "{$method} {$path} HTTP/1.1",
        'Host: 127.0.0.1:8080',
        'Connection: close',
        'Transfer-Encoding: chunked',
        ...$extraHeaders,
    ];
    $head = implode("\r\n", $lines) . "\r\n\r\n";

    return rawTcpHttpExchange($head . $chunkedBody);
}

it('decodes chunked application/x-www-form-urlencoded POST into $_POST', function () {
    $body = 'foo=bar&baz=1';
    $raw = chunkedRequest('POST', '/post', httpChunkedEncode([$body]), [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(httpResponseBody($raw))->toBeJson();
    expect(json_decode(httpResponseBody($raw), true))->toBe(['foo' => 'bar', 'baz' => '1']);
});

it('decodes chunked body split across multiple chunk frames', function () {
    $body = 'foo=bar&baz=1';
    $chunks = [substr($body, 0, 4), substr($body, 4)];
    $raw = chunkedRequest('POST', '/post', httpChunkedEncode($chunks), [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(json_decode(httpResponseBody($raw), true))->toBe(['foo' => 'bar', 'baz' => '1']);
});

it('accepts chunk-size line with extensions after semicolon', function () {
    $payload = 'hello';
    $chunked = dechex(strlen($payload)) . ';ext=1' . "\r\n" . $payload . "\r\n0\r\n\r\n";
    $raw = chunkedRequest('POST', '/post', $chunked, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(json_decode(httpResponseBody($raw), true))->toBe(['hello' => '']);
});

it('accepts leading zeros in chunk-size hex', function () {
    $payload = 'hello';
    $chunked = '005' . "\r\n" . $payload . "\r\n0\r\n\r\n";
    $raw = chunkedRequest('POST', '/post', $chunked, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(json_decode(httpResponseBody($raw), true))->toBe(['hello' => '']);
});

it('accepts uppercase hex digits in chunk-size', function () {
    $payload = str_repeat('x', 10);
    $chunked = 'A' . "\r\n" . $payload . "\r\n0\r\n\r\n";
    $raw = chunkedRequest('POST', '/post', $chunked, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(json_decode(httpResponseBody($raw), true))->toBe([$payload => '']);
});

it('decodes chunked application/json POST', function () {
    $json = '{"a":1,"b":[2,3]}';
    $raw = chunkedRequest('POST', '/post', httpChunkedEncode([$json]), [
        'Content-Type: application/json',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(json_decode(httpResponseBody($raw), true))->toBe(['a' => 1, 'b' => [2, 3]]);
});

it('handles empty chunked body (only terminator)', function () {
    $raw = chunkedRequest('POST', '/post', "0\r\n\r\n", [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(httpResponseBody($raw))->toBe('[]');
});

it('allows GET with chunked encoding and zero body for a normal response', function () {
    $raw = chunkedRequest('GET', '/', "0\r\n\r\n", []);

    expect(httpResponseStatus($raw))->toBe(200);
    expect(httpResponseBody($raw))->toBe('Hello World!');
});

it('maps trailing headers after the final chunk into $_SERVER for getallheaders', function () {
    $chunked = "0\r\nX-Chunk-Trailer: test-value\r\n\r\n";
    $raw = chunkedRequest('GET', '/headers', $chunked, []);

    expect(httpResponseStatus($raw))->toBe(200);
    $headers = json_decode(httpResponseBody($raw), true);
    expect($headers)->toBeArray();
    expect(array_change_key_case($headers))->toHaveKey('x-chunk-trailer');
    expect(array_change_key_case($headers)['x-chunk-trailer'])->toBe('test-value');
});

it('rejects message with both Content-Length and Transfer-Encoding', function () {
    $lines = [
        'POST /post HTTP/1.1',
        'Host: 127.0.0.1:8080',
        'Connection: close',
        'Content-Length: 10',
        'Transfer-Encoding: chunked',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    $head = implode("\r\n", $lines) . "\r\n\r\n";
    $raw = rawTcpHttpExchange($head . httpChunkedEncode(['foo=bar']));

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects two Transfer-Encoding header fields', function () {
    $lines = [
        'POST /post HTTP/1.1',
        'Host: 127.0.0.1:8080',
        'Connection: close',
        'Transfer-Encoding: chunked',
        'Transfer-Encoding: identity',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    $raw = rawTcpHttpExchange(implode("\r\n", $lines) . "\r\n\r\n" . httpChunkedEncode(['a=1']));

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects Transfer-Encoding that is not a single chunked token line', function () {
    $lines = [
        'POST /post HTTP/1.1',
        'Host: 127.0.0.1:8080',
        'Connection: close',
        'Transfer-Encoding: gzip',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    $raw = rawTcpHttpExchange(implode("\r\n", $lines) . "\r\n\r\n" . httpChunkedEncode(['a=1']));

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects Transfer-Encoding chunked list with additional codings on the same line', function () {
    $lines = [
        'POST /post HTTP/1.1',
        'Host: 127.0.0.1:8080',
        'Connection: close',
        'Transfer-Encoding: chunked, gzip',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    $raw = rawTcpHttpExchange(implode("\r\n", $lines) . "\r\n\r\n" . httpChunkedEncode(['a=1']));

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects non-hex chunk size line', function () {
    $raw = chunkedRequest('POST', '/post', "GGG\r\n", [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects chunk size field longer than 16 hex digits', function () {
    $tooLong = str_repeat('f', 17);
    $raw = chunkedRequest('POST', '/post', $tooLong . "\r\n", [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});

it('rejects chunk data not followed by CRLF', function () {
    $raw = chunkedRequest('POST', '/post', "3\r\nab\r\n0\r\n\r\n", [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    expect(httpResponseStatus($raw))->toBe(400);
    expect(httpResponseBody($raw))->toBe('');
});
