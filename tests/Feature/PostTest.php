<?php

it('get POST "application/x-www-form-urlencoded"', function (array $data) {
    $response = HttpClient()->post('/post', [
        'form_params' => $data,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->with('send data');


it('get POST "application/json"', function (array $data) {

    $response = HttpClient()->post('/post', [
        'json' => $data
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
   
})->with('send data');


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
})->with('send data');
