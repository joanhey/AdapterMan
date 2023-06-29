<?php

$requestHeaders = [
    'one var'           => [['Foo' => 'bar']],
    'two vars'          => [['Foo' => 'bar', 'Key' => 'hello Adapterman']],
    //'case insensitive'     => [['Foo' => 'bar', 'foo' => 'hello Adapterman']],
    // 'mixed'
];


it('tests Request Headers', function (array $data) {
    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toMatchArray($data);
})->with($requestHeaders);
