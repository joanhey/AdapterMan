<?php

it('tests HTTP method', function (string $method) {
    $response = HttpClient()->request($method,'/method');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getBody()->getContents())
        ->toBe($method);

})->with(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);


it('tests HTTP HEAD method', function () {
    $response = HttpClient()->head('/method');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getBody()->getContents())
        ->toBe('');
});


it('tests HTTP PATCH method', function () {
    $response = HttpClient()->patch('/method');

    expect($response->getBody()->getContents())
        ->toBe('PATCH');
})->todo();


it('tests HTTP BAD method', function () {
    $response = HttpClient()->request('BAD','/method');

    expect($response->getStatusCode())
        ->toBe(400)
        ->and($response->getBody()->getContents())
        ->toBe('');
});


it('tests HTTP lowercase method return 400', function (string $method) {
    
    $response = HttpClient()->request($method,'/method', [
        // force to use lowercase methods
        'curl' => [
            CURLOPT_CUSTOMREQUEST  => $method,
        ]
    ]);

    expect($response->getStatusCode())
        ->toBe(400)
        ->and($response->getBody()->getContents())
        ->toBe('');
})->with([
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

