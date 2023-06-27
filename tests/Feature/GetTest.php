<?php

it('tests GET', function () {
    $response = HttpClient()->get('/');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getHeaderLine('Server'))
        ->tobe('workerman')
        ->and($response->getHeaderLine('Content-Length'))
        ->tobe('16')
        ->and($response->getBody()->getContents())
        ->toBe('Hello Adapterman');
});

it('tests GET with query', function () {
    $data = [
        'foo' => 'bar',
        'key' => ['hello', 'Adapterman']
    ];

    $response = HttpClient()->get('/get', [
        'query' => $data
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
});
