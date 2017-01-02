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
    if (array_key_exists('usr_type', $_SESSION) && $_SESSION['usr_type'] == "admin") {
        return $next($request, $response);
    }

    $notFoundHandler = $this->get('notFoundHandler');
    return $notFoundHandler($request, $response);
};

// User Auth
$user_auth = function (Request $request, Response $response, $next) use ($app) {
    $route = $request->getAttribute('route');
    $urls = explode("/", $route->getPattern());
    $method = $urls[1];

    // Check login
    if (empty($_SESSION['usr_id'])) {
        return $response->withRedirect("/login");
    }

    // Check user
    $user = R::getRow("SELECT * FROM users WHERE usr_id = :usr_id", ['usr_id' => $_SESSION['usr_id']]);

    // Redirect user to login if not valid user
    if (!$user) {
        session_destroy();

        return $response->withRedirect("/login?url=" . $_SERVER['REQUEST_URI']);
    }

    // Fill user fields
    $app->extra['user'] = $user;


    if ($user['usr_type'] == "admin") {
        // full-access

        $connections = R::getAll("SELECT * FROM connections");
    } else {

        $permissions = R::getAll("SELECT * FROM permissions WHERE prm_user = :usr_id", ['usr_id' => $user['usr_id']]);

        $connection_ids = [];
        foreach ($permissions as $permission) {
            if ($permission['prm_permission_type'] == "full" || $permission['prm_permission_type'] == "partial") {
                $connection_ids[] = $permission['prm_connection'];
            }
        }

        if (count($connection_ids) == 0) {
            if ($method != "connections" && $method != "users") {
                if ($user['usr_type'] == "admin")
                    return $this->view->render($response, 'errors/no_connection_admin.html.twig', array_merge($app->extra));
                else
                    return $this->view->render($response, 'errors/no_connection.html.twig', array_merge($app->extra));
            }
        } else if (count($connection_ids) == 1) {
            $connections = R::getAll("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $connection_ids[0]]);
        } else {
            $connections = R::getAll("SELECT * FROM connections WHERE cnn_id IN (" . implode(',', $connection_ids) . ")");
        }
    }

    if ($method != "connections" && $method != "users") {
        if (count($connections) == 0) {
            if ($user['usr_type'] == "admin")
                return $this->view->render($response, 'errors/no_connection_admin.html.twig', array_merge($app->extra));
            else
                return $this->view->render($response, 'errors/no_connection.html.twig', array_merge($app->extra));
        }
    }

    $active_connection = false;

    // Fill connections
    $active_connection_id = R::getCell("SELECT atc_active_cnn_id FROM active_connections WHERE atc_usr_id = :cnn_user", ['cnn_user' => $user['usr_id']]);

    if ($active_connection_id) {
        foreach ($connections as $connection) {
            if ($active_connection_id == $connection['cnn_id']) {
                $active_connection = $connection;
            }
        }
    }

    if (!$active_connection) {
        $active_connection = $connections[0];

        R::exec("REPLACE INTO active_connections SET atc_usr_id = :usr_id, atc_active_cnn_id = :cnn_id", ['usr_id' => $user['usr_id'], 'cnn_id' => $active_connection['cnn_id']]);
    }

    $active_connection['cnn_settings'] = json_decode($active_connection['cnn_connection'], true);

    $app->extra['all_connections'] = $connections;
    $app->extra['active_connection'] = $active_connection;


    // Get Flash Messages
    $app->extra['messages'] = $this->flash->getMessages();

    return $next($request, $response);
};

// Add models
require __DIR__ . '/../src/Query.php';
require __DIR__ . '/../src/Connection.php';

// Add routes
require __DIR__ . '/../src/routes/home_route.php';
require __DIR__ . '/../src/routes/dashboard_route.php';
require __DIR__ . '/../src/routes/favorites_route.php';
require __DIR__ . '/../src/routes/query_route.php';
require __DIR__ . '/../src/routes/connections_route.php';
require __DIR__ . '/../src/routes/users_route.php';

$app->run();