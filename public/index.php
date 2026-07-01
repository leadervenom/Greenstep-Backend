<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv; // Import Dotenv namespace

// 1. Load Composer Autoloader
require __DIR__ . '/../vendor/autoload.php';

// 2. Bootstrap dotenv environment variables
// Reads the .env file from the project root directory safely
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

// 3. Instantiate Slim App
$app = AppFactory::create();

// changed code to allow CORS preflight requests
$app->options(

'/{routes:.+}',

function ($request,$response)
{
    return $response;
}
);

// 4. Explicitly load middleware files manually to bypass Composer autoloader bugs
require_once __DIR__ . '/../src/Middleware/JsonBodyParser.php';
require_once __DIR__ . '/../src/Middleware/Cors.php';

// 5. Instantiate and attach middleware class objects explicitly
$app->add(new \App\Middleware\JsonBodyParser());
$app->add(new \App\Middleware\Cors());

// 6. Add Built-in Slim Routing Middleware
$app->addRoutingMiddleware();

// 7. Global Error Handling Middleware
// Reads your APP_DEBUG environment key dynamically if preferred
$app->addErrorMiddleware(true, true, true);

// 8. Load Modular Application Routes
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// 9. Execute Application Lifecycle
$app->run();