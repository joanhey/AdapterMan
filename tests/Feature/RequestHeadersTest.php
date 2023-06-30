<?php

const REQUEST_HEADERS = [
    'empty'             => [[]],
    'one var'           => [['Foo' => 'bar']],
    'two vars'          => [['Foo' => 'bar', 'Key' => 'Hello Adapterman']],
    //'case insensitive'     => [['Foo' => 'bar', 'foo' => 'hello Adapterman']],
    // 'mixed'
];


it('get Request Headers', function (array $data) {
    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toMatchArray($data);
})->with(REQUEST_HEADERS);
