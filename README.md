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

### Symfony demo with Workerman
Symfony initialization 0ms

https://user-images.githubusercontent.com/249085/197399760-5da8311e-5cf1-426a-a89d-ec2a2de43af0.mp4

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


### Help with session issues
I was using this lib internally, for more than 2 years, to run legacy apps with Workerman.

We made it for APIs and microservices. So the session is not well tested.

#### Login progress
It's working with Symfony and Laravel

Laravel Orchid admin panel.
![image](https://user-images.githubusercontent.com/249085/197333441-74fff586-b984-492f-8cd1-58fb69774b1f.png)

Drupal showing public pages.
![image](https://user-images.githubusercontent.com/249085/197333512-0f840436-399f-4000-b9af-e6a05a7d30b2.png)

