# AdapterMan
AdapterMan for Workerman v3.5

Use almost any PHP app with Workerman, without touch 1 line of code in your fw or app.

If your app or fw use a Front Controller, 99% that will work. Requires minimun PHP 8.0.

Actually working with:
- Symfony
- Laravel
- Yii2
- Slim
- KumbiaHP
- ... (Your app?)

Still testing with more fws and apps.

## Installation
```
composer require joanhey/adapterman
```
Automatically install Workerman too.

## Tree
Where to create the files (`server.php` and `start.php`)

```
.
├── app(dir)
├── public(dir)
├── vendor(dir)
├── composer.json
├── server.php
└── start.php
```

## Server
server.php
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';


use Adapterman\Adapterman;
use Workerman\Worker;

Adapterman::init();

$http_worker                = new Worker('http://0.0.0.0:8080');
$http_worker->count         = (int) shell_exec('nproc') * 4;
$http_worker->name          = 'AdapterMan';

$http_worker->onWorkerStart = function () {
    //init();
    require __DIR__.'/start.php';
};

$http_worker->onMessage = static function ($connection, $request) {

    $connection->send(run());
};

Worker::runAll();

```
## Front Controller

It's different for any fw and app.

We are creating recipes for popular apps and frameworks.

- [Symfony](recipes/symfony.md)
- [Laravel](recipes/laravel.md)
- [Slim](recipes/slim.md)

Recommended `start.php` and leave `index.php` in public.

We can run the app with Workerman and with a webserver at the same time.


## Available commands in workerman
To run your app.

```php server.php start  ```  
```php server.php start -d  ```  
```php server.php status  ```  
```php server.php status -d  ```  
```php server.php connections```  
```php server.php stop  ```  
```php server.php stop -g  ```  
```php server.php restart  ```  
```php server.php reload  ```  
```php server.php reload -g  ```

Workerman documentation:
[https://github.com/walkor/workerman-manual](https://github.com/walkor/workerman-manual/blob/master/english/SUMMARY.md)


### Help with issues
I was using this lib internally, for more than 2 years, to run legacy apps with Workerman.

Almost all apps are used like microservices.

So the session is not ~well tested~ working for now.


