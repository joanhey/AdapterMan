# Laravel with Adapterman

## server.php

Create `server.php` in the project root directory with next content:
```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Adapterman\Adapterman;
use Workerman\Worker;

Adapterman::init();

$http_worker = new Worker('http://localhost:8080'); // or 127.0.0.1:8080, or localhost:9000
$http_worker->count = cpu_count(); // or any positive integer
$http_worker->name = env('APP_NAME'); // or any string

$http_worker->onWorkerStart = static function () {
    require __DIR__.'/start.php';
};

$http_worker->onMessage = static function ($connection, $request) {
    $connection->send(run());
};

Worker::runAll();
```

## start.php

Copy your `./public/index.php` to `./start.php`.

### Change the code

In the newly created `start.php`

Replace this part:
```php
/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);

```

With this part:
```php
/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app = require_once __DIR__.'/bootstrap/app.php';

global $kernel;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);


function run()
{
    global $kernel;

    ob_start();

    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);
    
    return ob_get_clean();
}


```

## Run your app

In the project root directory run:

```shell
php server.php start
``` 

View in your browser

```http://localhost:8080```
