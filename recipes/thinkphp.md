# Thinkphp with Adapterman

English | [中文](./thinkphp-cn.md)

## server.php

Create `server.php` in the project root directory with next content:
```php
<?php

use Adapterman\Adapterman;
use Adapterman\Http;
use Workerman\Worker;
use Workerman\Timer;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/start.php';

Adapterman::init();


$http_worker = new Worker('http://0.0.0.0:8080');
$http_worker->count = cpu_count() * 4;
$http_worker->name = "Adapterman"; // or any string

$http_worker->onWorkerStart = function (Worker $worker) {
    if ($worker->id === 0) {
        Timer::add(600, function () {
            Http::tryGcSessions();
        });
    }
};

$http_worker->onMessage = static function ($connection, $request) {
    $connection->send(run());
};

Worker::runAll();
```

## start.php

Copy your `./vendor/joanhey/adapterman/src/frameworks/think.php` to `./start.php`.


## Run your app

In the project root directory run:

```shell
php server.php start
``` 


More simple way:
```shell
# The first
./vendor/bin/adapterman start
# The second
/usr/bin/env php -c vendor/joanhey/adapterman/cli-php.ini vendor/joanhey/adapterman/src/start.php "$@"
```

View in your browser

```http://localhost:8080```
