<?php

dataset('REQUEST HEADERS', [
    'empty'             => [[]],
    'one var'           => [['Foo' => 'bar']],
    'two vars'          => [['Foo' => 'bar', 'Key' => 'Hello Adapterman']],
    'numerically index' => [['this', 'is', 'an', 'array']],
    'complex array'     => [
        'user' => [
            'name' => 'Bob Smith',
            'age'  => 47,
            'sex'  => 'M',
            'dob'  => '5/12/1956',
        ],
        'pastimes' => ['golf', 'opera', 'poker', 'rap'],
        'children' => [
            'bobby' => ['age'=>12, 'sex'=>'M'],
            'sally' => ['age'=>8, 'sex'=>'F'],
        ],
        'CEO',
    ],
    //'case insensitive'     => [['Foo' => 'bar', 'foo' => 'hello Adapterman']],
]);


it('get Request Headers', function (array $data) {
    $response = HttpClient()->get('/headers', [
        'headers' => $data,
    ]);

    $content = $response->getBody()->getContents();
    expect($content)
        ->toBeJson()
        ->json()
        ->toBeArray();

    // convert all keys to lowercase in validation
    // header names are case insensitive according to the HTTP specification
    $data = array_change_key_case($data);
    $content = array_change_key_case(json_decode($content, true));

    expect($content)
        ->toMatchArray($data);

})->with('REQUEST HEADERS');

it('get headers', function (string $key, string $value) {
    $response = HttpClient()->get('/headers');
    
    $content = $response->getBody()->getContents();
    expect($content)
        ->toBeJson()
        ->json()
        ->toBeArray();

    // convert all keys to lowercase in validation
    // header names are case insensitive according to the HTTP specification
    $content = array_change_key_case(json_decode($content, true));
    expect($content)
        ->{strtolower($key)}
        ->toBe($value);

})->with([
    ['Host', '127.0.0.1:8080'],
    ['User-Agent', 'Testing/1.0']
]);
