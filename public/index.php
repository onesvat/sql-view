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

// Admin Auth
$admin_auth = function (Request $request, Response $response, $next) use ($app) {

    // Check login
    if (!array_key_exists('admin', $_SESSION) || $_SESSION['admin'] != true) {
        return $response->withRedirect("/login");
    }

    // Check user
    $user = R::getRow("SELECT * FROM users WHERE usr_id = :usr_id AND usr_type = 'admin'", ['usr_id' => $_SESSION['usr_id']]);

    if (!$user) {
        return $response->withRedirect("/login?url=" . $_SERVER['REQUEST_URI']);
    }
    // Fill user fields
    $app->extra['user'] = $user;


    // Fill connections
    $active_connection = R::getRow("SELECT * FROM connections INNER JOIN active_connections ON cnn_id = atc_active_cnn_id WHERE atc_usr_id = :usr_id", ['usr_id' => $user['usr_id']]);

    $all_connections = R::getAll("SELECT * FROM connections INNER JOIN permissions ON cnn_id = prm_connection_id WHERE prm_usr_id = :usr_id", ['usr_id' => $user['usr_id']]);

    $active_connection['connection'] = json_decode($active_connection['cnn_connection'], true);

    $app->extra['all_connections'] = $all_connections;
    $app->extra['active_connection'] = $active_connection;

    // Get Flash Messages
    $app->extra['messages'] = $this->flash->getMessages();

    if (!empty($active_connection['connection']['cnn_port'])) {
        $active_connection['connection']['cnn_host'] = $active_connection['connection']['cnn_host'] . ':' . $active_connection['connection']['cnn_port'];
    }
    try {
        $app->connection = new PDO(
            "mysql:host=" . $active_connection['connection']['cnn_host'] . ";dbname=" . $active_connection['connection']['cnn_database'],
            $active_connection['connection']['cnn_username'],
            $active_connection['connection']['cnn_password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]
        );
    } catch (Exception $e) {
        $app->extra['connection_error'] = "true";
    }


    $app->extra['active_connection'] = $active_connection;
    return $next($request, $response);
};

// User Auth
$user_auth = function (Request $request, Response $response, $next) use ($app) {

    // Check login
    if (empty($_SESSION['usr_id'])) {
        return $response->withRedirect("/login");
    }

    // Check user
    $user = R::getRow("SELECT * FROM users WHERE usr_id = :usr_id", ['usr_id' => $_SESSION['usr_id']]);

    if (!$user) {
        return $response->withRedirect("/login?url=" . $_SERVER['REQUEST_URI']);
    }
    // Fill user fields
    $app->extra['user'] = $user;


    // Fill connections
    $active_connection = R::getRow("SELECT * FROM connections INNER JOIN active_connections ON cnn_id = atc_active_cnn_id WHERE atc_usr_id = :usr_id", ['usr_id' => $user['usr_id']]);

    $all_connections = R::getAll("SELECT * FROM connections INNER JOIN permissions ON cnn_id = prm_connection_id WHERE prm_usr_id = :usr_id", ['usr_id' => $user['usr_id']]);


    $active_connection['connection'] = json_decode($active_connection['cnn_connection'], true);

    $app->extra['all_connections'] = $all_connections;
    $app->extra['active_connection'] = $active_connection;

    // Get Flash Messages
    $app->extra['messages'] = $this->flash->getMessages();

    try {
        if ($app->extra['active_connection'] == "mysql") {
            $app->connection = new PDO(
                "mysql:host=" . $active_connection['connection']['cnn_host'] . ':' . $active_connection['connection']['cnn_port'] . ";dbname=" . $active_connection['connection']['cnn_database'],
                $active_connection['connection']['cnn_username'],
                $active_connection['connection']['cnn_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else if ($app->extra['active_connection'] == "postgresql") {
            $app->connection = new PDO(
                "pgsql:host=" . $active_connection['connection']['cnn_host'] . ";dbname=" . $active_connection['connection']['cnn_database'],
                $active_connection['connection']['cnn_username'],
                $active_connection['connection']['cnn_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else {
            throw new Exception("Type failed");
        }

    } catch (Exception $e) {
        var_dump($e->getMessage());
        $app->extra['connection_error'] = "true";
    }

    $app->extra['active_connection'] = $active_connection;
    return $next($request, $response);
};

// Add models
require __DIR__ . '/../src/Query.php';

// Add routes
require __DIR__ . '/../src/routes/home_route.php';
require __DIR__ . '/../src/routes/dashboard_route.php';
require __DIR__ . '/../src/routes/favorite_route.php';
require __DIR__ . '/../src/routes/query_route.php';
require __DIR__ . '/../src/routes/connections_route.php';
require __DIR__ . '/../src/routes/users_route.php';

$app->run();