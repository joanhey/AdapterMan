<?php

it('tests HTTP Methods', function (string $method) {
    $response = HttpClient()->request($method,'/method');

    expect($response->getBody()->getContents())
        ->toBe($method);

})->with(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);


it('tests HEAD method', function () {
    $response = HttpClient()->head('/method');

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getBody()->getContents())
        ->toBe('');
});


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

