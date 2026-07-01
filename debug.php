<?php
require 'vendor/autoload.php';
(Dotenv\Dotenv::createImmutable('.'))->safeLoad();

$app = Slim\Factory\AppFactory::create();
$routes = require 'src/routes.php';
$routes($app);

foreach ($app->getRouteCollector()->getRoutes() as $r) {
    echo implode(',', $r->getMethods()) . ' ' . $r->getPattern() . PHP_EOL;
}