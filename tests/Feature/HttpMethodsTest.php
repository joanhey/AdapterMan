<?php

it('get HTTP method', function (string $method) {
    $response = HttpClient()->request($method,'/method');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getBody()->getContents())
        ->toBe($method);

})->with(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH']);


it('get HTTP HEAD method and not return body', function () {
    $response = HttpClient()->head('/method');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getBody()->getContents())
        ->toBe('');
});

it('get HTTP BAD method return 400', function () {
    $response = HttpClient()->request('BAD','/method');

    expect($response->getStatusCode())
        ->toBe(400)
        ->and($response->getBody()->getContents())
        ->toBe('');
});


it('get HTTP lowercase method return 400', function (string $method) {
    // libcurl may normalize the method to uppercase; send the request line over TCP instead.
    $raw = rawTcpHttpRequest($method, '/method');
    [$headerBlock, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');

    expect($headerBlock)->toStartWith('HTTP/1.1 400');
    expect($body)->toBe('');
})->skip(
    featureHttpTestsTargetNativeWorkerman(),
    'Skipped when ADAPTERMAN_TEST_HTTP_SERVER=workerman (wire-level lowercase method semantics differ from Adapterman).'
)->with([
    'get',
    'Get',
    'post',
    'Post',
    'put',
    'Put',
    'delete',
    'Delete',
    'patch',
    'Patch',
    'head',
    'Head',
    'options',
    'Options'
]);

