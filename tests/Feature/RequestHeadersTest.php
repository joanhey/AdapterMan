<?php

dataset('REQUEST HEADERS', [
    'empty'             => [[]],
    'one var'           => [['Foo' => 'bar']],
    'two vars'          => [['Foo' => 'bar', 'Key' => 'Hello Adapterman']],
    //'case insensitive'     => [['Foo' => 'bar', 'foo' => 'hello Adapterman']],
    // 'mixed'
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
