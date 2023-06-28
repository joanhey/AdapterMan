<?php

it('tests GET method', function () {
    $response = HttpClient()->get('/method');

    expect($response->getBody()->getContents())
        ->toBe('GET');
});


it('tests POST method', function () {
    $response = HttpClient()->post('/method');

    expect($response->getBody()->getContents())
        ->toBe('POST');
});


it('tests PATCH method', function () {
    $response = HttpClient()->patch('/method');

    expect($response->getBody()->getContents())
        ->toBe('PATCH');
})->todo();


it('tests PUT method', function () {
    $response = HttpClient()->put('/method');

    expect($response->getBody()->getContents())
        ->toBe('PUT');
});


it('tests DELETE method', function () {
    $response = HttpClient()->delete('/method');

    expect($response->getBody()->getContents())
        ->toBe('DELETE');
});


it('tests BAD method', function () {
    $response = HttpClient()->request('BAD','/method');

    expect($response->getBody()->getContents())
        ->toBe('POST');
})->todo();

