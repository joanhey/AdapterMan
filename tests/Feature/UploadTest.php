<?php

use GuzzleHttp\Psr7;

dataset('UPLOAD', [
    'file_composer' => [[
        'file'     => 'file_composer',
        'contents' => Psr7\Utils::tryFopen(__DIR__ .'/Stub/composer.json', 'r'),
        'expect'   => [
                'name' => 'composer.json',
                'full_path' => 'composer.json',
                'size' => filesize(__DIR__ .'/Stub/composer.json'),
                'error' => 0,
                'type' => 'application/json',
                //'tmp_name' is random each time
        ]
    ]]
]);

it('check $_FILES with composer.json', function ($data) {
    $response = HttpClient()->post('/upload', [
        'multipart' => [
            [
                'name'     => $data['file'],
                'contents' => $data['contents'],
            ],
        ],
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toHaveCount(1)
        ->toHaveKey($data['file'])
        ->{$data['file']}
            ->toMatchArray($data['expect'])
            ->toHaveKey('tmp_name')
        ->{$data['file']}->tmp_name
            ->toBeFile();

})->with('UPLOAD');


it('get POST Multipart with files', function (array $data) {

    $multipart = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $key2 => $value2) {
                $calcKey = array_is_list($value) ? '[]' : "[$key2]";
                $multipart[] = [
                    'name' => $key.$calcKey,
                    'contents' => $value2,
                ];
            }

            continue;
        }

        $multipart[] = [
            'name' => $key,
            'contents' => $value,
        ];
    }

    $response = HttpClient()->post('/post', [
        'multipart' => $multipart,
    ]);

    expect($response->getBody()->getContents())
        ->toBeJson()
        ->json()
        ->toBe($data);
})->with('send data');
