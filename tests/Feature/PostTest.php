<?php

dataset('POST DATA', [
    'empty'             => [[]], 
    'one var'           => [['foo' => 'bar']],
    'two vars'          => [['foo' => 'bar', 'key' => 'hello Adapterman']],
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
        
       // with headers ...
]);


it('get POST "application/x-www-form-urlencoded"', function (array $data) {
    $response = HttpClient()->post('/post', [
        'form_params' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->with('POST DATA');


it('get POST "application/json"', function (array $data) {

    $response = HttpClient()->post('/post', [
        'json' => $data
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
   
})->with('POST DATA');


it('get POST "multipart/form-data"', function (array $data) {

    $multipart = [];
    foreach($data as $key => $value) {
        if(is_array($value)) {
            foreach($value as $key2 => $value2) {
                $calcKey = array_is_list($value) ? '[]' : "[$key2]";
                $multipart[] = [
                    'name'     => $key . $calcKey,
                    'contents' => $value2,
                ];
            }
            continue;
        }

        $multipart[] = [
            'name'     => $key,
            'contents' => $value,
        ];
    }

    $response = HttpClient()->post('/post', [
            'multipart' => $multipart
    ]);
    
    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->with('POST DATA');
