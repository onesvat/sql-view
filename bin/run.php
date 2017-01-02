<?php

use RedBeanPHP\R;

$argv = $GLOBALS['argv'];
array_shift($GLOBALS['argv']);
$pathInfo = '/' . implode('/', $argv);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/application.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

date_default_timezone_set($_ENV['APP_TIMEZONE']);
ini_set('date.timezone', $_ENV['APP_TIMEZONE']);
ini_set("display_errors", 1);
session_start();

// Instantiate the app
$app = new Application([
    'settings' => [
        'displayErrorDetails' => true,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => __DIR__ . '/../cache/',
        ]
    ],
    'environment' => \Slim\Http\Environment::mock([
        'REQUEST_URI' => $pathInfo
    ])
]);

// Get container
$container = $app->getContainer();

// Add functions
require __DIR__ . '/../src/functions.php';

// Add dependencies
require __DIR__ . '/../src/dependencies.php';

// Add database
R::setup('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], true);


// Add models
require __DIR__ . '/../src/Query.php';
require __DIR__ . '/../src/Connection.php';

// Add routes
require __DIR__ . '/../src/routes/cron_route.php';

$app->run();