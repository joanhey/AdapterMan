# Thinkphp 使用 Adapterman

[English](./thinkphp.md) | 中文

## server.php

根据以下代码创建 `server.php` 在你的项目根目录然后下一步:
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

复制`./vendor/joanhey/adapterman/src/frameworks/think.php` 到 `./start.php`.


## 开始使用

在你的根目录运行以下命令:

```shell
php server.php start
``` 

其他更简单的启动方式
```shell
# 第一种
./vendor/bin/adapterman start
# 第二种
/usr/bin/env php -c vendor/joanhey/adapterman/cli-php.ini vendor/joanhey/adapterman/src/start.php "$@"
```

在浏览器访问

```http://localhost:8080```
