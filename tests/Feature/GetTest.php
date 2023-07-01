<?php

it('tests GET', function () {
    $response = HttpClient()->get('/');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getHeaderLine('Server'))
        ->tobe('workerman')
        ->and($response->getHeaderLine('Content-Length'))
        ->tobe('12')
        ->and($response->getBody()->getContents())
        ->toBe('Hello World!');
});


it('tests GET Query string', function (array $data) {
    $response = HttpClient()->get('/get', [
        'query' => $data
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->with([
    'one var'           => [['foo' => 'bar']],
    'two vars'          => [['foo' => 'bar', 'key' => 'Hello Adapterman']],
    'indexed-array'     => [['indexed-array' => ['this', 'is', 'an', 'array']]],
    'associative-array' => [['associative-array' => [
        'foo'   => 'bar',
        'hello' => 'Adapterman', 
        ]
    ]],
    //'multidimensional-array' => [[]],
    'all mixed' => [[
            'foo' => 'bar',
            'key' => 'Hello Adapterman',
            'indexed-array' => ['this', 'is', 'an', 'array'],
            'associative-array' => [
                'foo' => 'bar',
                'hello' => 'Adapterman', 
            ],
        ]],
]);

