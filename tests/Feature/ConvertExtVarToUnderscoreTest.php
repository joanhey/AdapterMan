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


it('convert POST var name to underscore', function (string $name, string $converted) {
    $response = HttpClient()->post('/post', [
        'form_params' => [$name, 'test']
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([$converted, 'test']);
})->with('ext vars to underscore')
->todo();

it('convert POST multipart var name to underscore', function (string $name, string $converted) {
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
