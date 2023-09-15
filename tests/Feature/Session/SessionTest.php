<?php

use GuzzleHttp\Cookie\CookieJar;

dataset('COOKIES', [
    'set two' => [['set' => [
        'foo'   => 'bar',
        'hello' => 'Adapterman', 
    ]]],
    'delete one' => [['delete' => ['foo']]],
    'delete both' => [['delete' => ['foo', 'hello']]],
    //'case insensitive'     => [['Foo' => 'bar', 'foo' => 'hello Adapterman']],
]);



it('set SESSION values', function () {
    $cookie = new CookieJar();
    $responseSet = HttpClient()->get('/session', [
        'cookies' => $cookie,
        'query' => ['set' => [
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]],
    ]);

    expect($responseSet->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman',
        ]);
    
    $response = HttpClient()->get('/session', [
        'cookies' => $cookie
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

});


it('delete SESSION values', function () {
    $cookie = new CookieJar();
    $responseSet = HttpClient()->get('/session', [
        'cookies' => $cookie,
        //'headers' => ['Cookie' => 'PHPSESSID=asdf131sd3f132sd1f3as'],
        'query' => ['set' => [
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]],
    ]);

    expect($responseSet->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman',
        ]);

    $response = HttpClient()->get('/session', [
        'cookies' => $cookie
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    $responseDelete = HttpClient()->get('/session', [
        'cookies' => $cookie,
        'query' => ['delete' => [
            'foo',
            //'hello', 
        ]],
    ]);

    expect($responseDelete->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            //'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    $response = HttpClient()->get('/session', [
        'cookies' => $cookie
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            //'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    // expect($cookie->getCookieByName('foo')->toArray())
    //     ->not->toMatchArray([
    //         'Name' => 'foo',
    //         'Value' => 'bar',
    //     ])
    //     //->toBe('') // to debug 
    //     ->and($cookie->getCookieByName('hello')->toArray())
    //     ->toMatchArray([
    //         'Name' => 'hello',
    //         'Value' => 'Adapterman',
    //     ]);
});

it('send SESSION start', function () {
    $cookie = new CookieJar();
    $responseSet = HttpClient()->get('/session/start', [
        //'cookies' => ['PHPSESSID' => 'asdf131sd3f132sd1f3as'],
        ['cookies' => $cookie],
        //'headers' => ['Cookie' => 'PHPSESSID=d138dcd8205e1d2c1e6a6c6e1d25586f'],
        // 'query' => ['set' => [
        //     'foo'   => 'bar',
        //     'hello' => 'Adapterman', 
        // ]],
    ]);

    expect($responseSet->getHeader('Set-Cookie') )
         ->toBe([]);

    expect($responseSet->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBeArray();
        // ->toBe([
        //     'foo'   => 'bar',
        //     'hello' => 'Adapterman',
        // ]);
    
    $sessionID = json_decode($responseSet->getBody()->getContents(), true)['id'];
    //echo $sessionID;
    $response = HttpClient()->get('/session/start', [
        ['cookies' => $cookie],
        //'headers' => ['Cookie' => "PHPSESSID=$sessionID"],
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

});

it('check invalid SESSION id', function () {
    //$cookie = new CookieJar();
    $responseSet = HttpClient()->get('/session/start', [
        //'cookies' => $cookie,
        //'headers' => ['Cookie' => 'PHPSESSID=/·$asdf131sd3f132sd1f3as'],
        'headers' => ['Cookie' => 'PHPSESSID=2beaae15a782f187b956ce14f50565ec'],
        // 'query' => ['set' => [
        //     'foo'   => 'bar',
        //     'hello' => 'Adapterman', 
        //    ]
        //], // validate that session_id changed
    ]);

    expect($responseSet->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([]);
});

// test 2 users in the same expectation intermixed
// $user1 = new CookieJar(), $user2 = new CookieJar()
it('work with 2 users SESSION values', function () {
    $user1 = new CookieJar();
    $responseUser1 = HttpClient()->get('/session', [
        'cookies' => $cookie,
        //'headers' => ['Cookie' => 'PHPSESSID=asdf131sd3f132sd1f3as'],
        'query' => ['set' => [
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]],
    ]);

    expect($responseUser1->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman',
        ]);

    $response = HttpClient()->get('/session', [
        'cookies' => $user1
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    $responseDelete = HttpClient()->get('/session', [
        'cookies' => $cookie,
        'query' => ['delete' => [
            'foo',
            //'hello', 
        ]],
    ]);

    expect($responseDelete->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            //'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    $response = HttpClient()->get('/session', [
        'cookies' => $cookie
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe([
            //'foo'   => 'bar',
            'hello' => 'Adapterman', 
        ]);

    // expect($cookie->getCookieByName('foo')->toArray())
    //     ->not->toMatchArray([
    //         'Name' => 'foo',
    //         'Value' => 'bar',
    //     ])
    //     //->toBe('') // to debug 
    //     ->and($cookie->getCookieByName('hello')->toArray())
    //     ->toMatchArray([
    //         'Name' => 'hello',
    //         'Value' => 'Adapterman',
    //     ]);
});
