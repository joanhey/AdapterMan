<?php

it('tests 404 GET', function () {
    $response = HttpClient()->get('/404');
    
    expect($response->getStatusCode())
        ->toBe(404)
        ->and($response->getBody()->getContents())
        ->toBe('404 Not Found');
});


