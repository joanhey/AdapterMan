<p align="center">
  <img src="https://user-images.githubusercontent.com/249085/200214022-c2c45753-368c-4e28-8415-2b3cadca9907.png" />
</p>
Faster and more scalable apps, also use it as Serverless.

Run almost any PHP app with the async event driven Workerman, without touch 1 line of code in your fw or app.

If your app or fw use a Front Controller, 99% that will work. Requires minimun PHP 8.0.

Actually working with:
- Symfony
- Laravel
- CakePHP
- Yii2
- Slim
- KumbiaPHP
- ThinkPHP
- ... (Your app?)

Still testing with more fws and apps.
Without touch a line of code.

### NEW !!! Workerman shared nothing mode
We started to test it. Experimental still.

Each request is independent and load the .php file, like with PHP-FPM.
Using the same .php files.

![image](https://user-images.githubusercontent.com/249085/209589643-5b464656-4d44-4879-9bbc-deb49582a57b.png)


Framework | JSON | 1-query | Multiple queries | Fortunes | Updates | Plaintext
-- | -- | -- | -- | -- | -- | --
php php-fpm| 187,747 | 97,658 | 12,784 | 79,309 | 2,010 | 195,283
php workerman | 822,930 | 134,475 | 15,648 | 124,923 | 4,683 | 1,161,016



## Performance bench Worker mode
Results from **Techempower benchmark.
Without touch a line of code.**

Follow https://twitter.com/adaptermanphp for more updates.


### Symfony 6
With full ORM
![image](https://user-images.githubusercontent.com/249085/209320777-13d1cc25-f350-43a4-ba2f-4db0a92c7b7a.png)
Latency
![image](https://user-images.githubusercontent.com/249085/209321052-6dab2d0e-c630-48d8-a25c-5f1906f08b8f.png)


Fw | Plaintext | Json | Single query | Multiple query | Updates | Fortunes
 -- | --| -- | -- | -- | -- | -- 
Symfony | 38,231 | 37,557 | 12,578 | 10,741 | 3,420 | 10,741
**Symfony Workerman** | **210,796** | **197,059** | **107,050** | **13,401** | **4,062** | **71,092**

### Laravel 8
With full ORM.

 Fw | Plaintext | Json | Single query | Multiple query | Updates | Fortunes
 -- | --| -- | -- | -- | -- | -- 
Laravel | 14,799 | 14,770 | 9,263 | 3,247 | 1,452 | 8,354
Laravel Roadrunner | 482 | 478 | 474 | 375 | 359 | 472
Laravel Swoole | 38,824 | 37,439 | 21,687 | 3,958 | 1,588 | 16,035 
Laravel Laravel s | 54,617 | 49,372 | 23,677 | 2,917 | 1,255 | 16,696 
**Laravel Workerman** | **103,004** | **99,891** | **46,001** | **5,828** | **1,666** | **27,158** 

![image](https://user-images.githubusercontent.com/249085/200189417-06fa658b-92c3-4c6d-a6e4-1efb3446a513.png)
Latency
![image](https://user-images.githubusercontent.com/249085/200189427-99977bb7-5910-4d17-a47c-7242e8f95f8f.png)



### Slim with Workerman
Without ORM
![image](https://user-images.githubusercontent.com/249085/201919385-ad25e41b-9887-42b7-92c0-d524a5e6aeae.png)

Framework | Plaintext | JSON | 1-query | 20-query | Updates | Fortunes 
-- | -- | -- | -- | -- | -- | --
Slim 4   | 35,251 | 38,305 | 34,272 | 12,579 | 2,097 | 32,634  
**Slim 4 Workerman** | **134,531** | **129,393** | **81,889** | **15,803** | **2,456** | **73,212** 
Slim 4 Workerman pgsql * |   |   | 102,926 | 19,637 | 14,875 | 92,752 

* Without ORM and db class optimized for Workerman

### Symfony demo with Workerman
Symfony initialization 0ms and half the time per request.

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
$http_worker->count         = 8;
$http_worker->name          = 'AdapterMan';

$http_worker->onWorkerStart = static function () {
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
- [Lumen](recipes/lumen.md)
- [Slim](recipes/slim.md)

Recommended `start.php` and leave `index.php` in public.

We can run the app with Workerman and with php-fpm at the same time.


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

You can also select a diferent cli php.ini directly:

```php -c cli-php.ini server.php start```

### Help with session issues
I was using this lib internally, for more than 2 years, to run legacy apps with Workerman.

We made it for APIs and microservices. So the session is not well tested.

#### Login progress
It's working with Symfony and Laravel

Laravel Orchid admin panel.
![image](https://user-images.githubusercontent.com/249085/197333441-74fff586-b984-492f-8cd1-58fb69774b1f.png)

Drupal showing public pages.
![image](https://user-images.githubusercontent.com/249085/197333512-0f840436-399f-4000-b9af-e6a05a7d30b2.png)

