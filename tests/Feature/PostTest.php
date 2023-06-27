<?php

it('tests POST', function () {
    $data = [
        'foo' => 'bar',
        'key' => ['hello', 'Adapterman']
    ];

    $response = HttpClient()->post('/post', [
            'form_params' => $data,
    ]);
    
    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
});

it('tests POST JSON', function () {
    $data = [
        'foo' => 'bar',
        'key' => ['hello', 'Adapterman']
    ];

    $response = HttpClient()->post('/post', [
        'json' => $data
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
   
});

it('tests POST Multipart', function () {
    $data = [
        'foo' => 'bar',
        'key' => ['hello', 'Adapterman']
    ];

    $response = HttpClient()->post('/post', [
            'form_params' => $data,
    ]);
    
    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->todo();
