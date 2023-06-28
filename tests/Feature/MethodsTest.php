<?php

it('tests HTTP Methods', function (string $method) {
    $response = HttpClient()->request($method,'/method');

    expect($response->getBody()->getContents())
        ->toBe($method);
})->with(['GET', 'POST', 'PUT', 'DELETE']);


it('tests PATCH method', function () {
    $response = HttpClient()->patch('/method');

    expect($response->getBody()->getContents())
        ->toBe('PATCH');
})->todo();


it('tests BAD method', function () {
    $response = HttpClient()->request('BAD','/method');

    expect($response->getBody()->getContents())
        ->toBe('POST');
})->todo();

