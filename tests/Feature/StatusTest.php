<?php

it('tests 200 GET', function () {
    $response = HttpClient()->get('/');

    expect($response->getStatusCode())
        ->toBe(200);
});


it('tests 404 GET', function () {
    $response = HttpClient()->get('/404');

    expect($response->getStatusCode())
        ->toBe(404)
        ->and($response->getBody()->getContents())
        ->toBe('404 Not Found');
});

