<?php

use Middlewares\TrailingSlash;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
$app->add(new TrailingSlash());

require __DIR__ . '/../App/routes.php';

$app->run();
