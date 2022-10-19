# Slim with Workerman

Copy your `app/index.php` to `start.php`.

## Change the code
In `start.php`

Change:
```php
$app->run();
```

To:
```php
function run(): string
{
    global $app;
    ob_start();

    $app->run();

    return ob_get_clean();
}


```
And add `global $app;` before create the `$app` variable.

```php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

global $app; // workerman

AppFactory::setContainer(new \DI\Container());
$app = AppFactory::create();

```

## Run your app
```php server.php start  ``` 


View in your browser

```http://localhost:8080```