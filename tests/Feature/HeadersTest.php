<?php

it('tests Header', function () {
    $data = [
        'Foo' => 'bar'
    ];

    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->json()
        ->toMatchArray($data);
});


it('tests Headers', function () {
    $data = [
        'Foo' => 'bar',
        'Key' => 'Adapterman'
    ];

    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toMatchArray($data);
});


it('tests Headers Case Insensitive', function () {
    $data = [
        'foo' => 'bar',
        'key' => 'Adapterman'
    ];

    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        //->toMatchArray($data);
        ->toBe($data);
})->todo();
