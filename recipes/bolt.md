# Bolt with Workerman

Copy your `app/index.php` to `start.php`.

## Change the code
In `start.php`

Change:
```php
<?php

declare(strict_types=1);

use Bolt\Configuration\Config;
use Bolt\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

// Set the `time_limit` and `memory_limit`, if we're allowed to
set_time_limit(0);
if (Config::convertPHPSizeToBytes(ini_get('memory_limit')) < 1073741824) {
    @ini_set('memory_limit', '1024M');
}

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

To:
```php
<?php

declare(strict_types=1);

use Bolt\Configuration\Config;
use Bolt\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

// Set the `time_limit` and `memory_limit`, if we're allowed to
//set_time_limit(0);
//if (Config::convertPHPSizeToBytes(ini_get('memory_limit')) < 1073741824) {
//    @ini_set('memory_limit', '1024M');
//} Change it directly in the php.ini

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

global $kernel;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

function run(): string
{
    global $kernel;

    ob_start();

    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
    
    return ob_get_clean();
}

```

## Run your app
```php server.php start  ``` 


View in your browser

```http://localhost:8080```
