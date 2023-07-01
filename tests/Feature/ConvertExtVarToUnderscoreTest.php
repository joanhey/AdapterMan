<?php

it('converts GET Query var name to underscore', function (string $name, string $converted) {
    $response = HttpClient()->get('/get', [
        'query' => [$name => 'test']
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([$converted => 'test']);
})->with('ext vars to underscore');


it('convert POST "application/x-www-form-urlencoded" var name to underscore', function (string $name, string $converted) {
    $response = HttpClient()->post('/post', [
        //'form_params' => [],
        // force send raw data
        'curl' => [
            CURLOPT_HTTPHEADER  => ["application/x-www-form-urlencoded"],
            CURLOPT_POSTFIELDS  => "$name=test",
        ]
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([$converted => 'test']);
})->with('ext vars to underscore');


it('convert POST "multipart/form-data" var name to underscore', function (string $name, string $converted) {
    $response = HttpClient()->post('/post', [
        'multipart' => [[
            'name'     => $name,
            'contents' => 'test',
        ]]
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([$converted => 'test']);
})->with('ext vars to underscore');
