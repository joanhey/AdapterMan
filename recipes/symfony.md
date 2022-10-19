# Symfony with Workerman

Copy your `app/index.php` to `start.php`.

## Change the code
In `start.php`

Change:
```php
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

To:
```php
global $kernel;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

function run()
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
