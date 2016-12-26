<?php

use RedBeanPHP\R;
use Slim\Http\Request;
use Slim\Http\Response;

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
]);

// Get container
$container = $app->getContainer();

// Add functions
require __DIR__ . '/../src/functions.php';

// Add dependencies
require __DIR__ . '/../src/dependencies.php';

// Add database
R::setup('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], true);

// User Auth
$user_auth = function (Request $request, Response $response, $next) use ($app) {

    // Check login
    if (!array_key_exists('login', $_SESSION) || $_SESSION['login'] != true) {
        return $response->withRedirect("/login");
    }

    // Check user
    $user = R::getRow("SELECT * FROM users WHERE usr_id = :usr_id", ['usr_id' => $_SESSION['usr_id']]);

    if (!$user) {
        return $response->withRedirect("/login?url=" . $_SERVER['REQUEST_URI']);
    }
    // Fill user fields
    $app->extra['user'] = $user;

    // Get Flash Messages
    $app->extra['messages'] = $this->flash->getMessages();

    return $next($request, $response);
};

// Add routes
require __DIR__ . '/../src/routes/home_route.php';
require __DIR__ . '/../src/routes/dashboard_route.php';
require __DIR__ . '/../src/routes/query_route.php';
require __DIR__ . '/../src/routes/setting_route.php';

$app->run();