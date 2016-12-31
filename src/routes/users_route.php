<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/users', function (Request $request, Response $response, $args) use ($app) {

    $args['users'] = R::getAll("SELECT * FROM users");

    return $this->view->render($response, 'users.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/permissions/connections/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections LEFT JOIN permissions ON cnn_user = prm_usr_id WHERE cnn_user = :cnn_user", ['cnn_user' => $args['usr_id']]);
    foreach ($args['connections'] as $key => $connection) {
        if ((!empty($connection['prm_table_name'])) || (!empty($connection['field_name']))) {
            $args['connections'][$key]['give_permission_button'] = 'customized';
        }
        if (empty($connection['prm_connection_id'])) {
            $args['connections'][$key]['give_permission_button'] = 'enabled';
        } else {
            $args['connections'][$key]['give_permission_button'] = 'disabled';
        }
    }

    return $this->view->render($response, 'user_connection_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/connection/give_permission/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $connection_id = $args['connection_id'];

    R::exec("DELETE FROM permissions WHERE prm_connection_id = :prm_connection_id AND prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $connection_id]);

    R::exec("INSERT INTO permissions(prm_connection_id,prm_usr_id) VALUES(:prm_connection_id,:prm_usr_id)", ['prm_connection_id' => $connection_id, 'prm_usr_id' => $args['usr_id']]);

    return $response->withRedirect('/users/permissions/connections/' . $args['usr_id']);


})->add($admin_auth);


$app->get('/users/connection/remove_permission/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $connection_id = $args['connection_id'];

    R::exec("DELETE FROM permissions WHERE prm_connection_id = :prm_connection_id AND prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $connection_id]);

    return $response->withRedirect('/users/permissions/connections/' . $args['usr_id']);


})->add($admin_auth);


$app->get('/users/permissions/tables/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {


    $active_connection = R::getRow("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['connection_id']]);
    $active_connection['connection'] = json_decode($active_connection['cnn_connection'], true);

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

        $stmt = $app->connection->prepare("SHOW TABLES");
        $stmt->execute(['table_schema' => $active_connection['connection']['cnn_database']]);
        $args['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        echo 'Connection failed' . $e->getMessage();
        die;
    }

    foreach ($args['tables'] as $key => $table_name) {
        $table_permission = R::getRow("SELECT * FROM permissions WHERE prm_usr_id = :prm_usr_id AND prm_connection_id = :prm_connection_id AND prm_table_name = :prm_table_name", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $args['connection_id'], 'prm_table_name' => $table_name]);
        if (empty($table_permission)) {
            $args['table_permission'][$key]['give_permission_button'] = 'enabled';
        } else {
            $args['table_permission'][$key]['give_permission_button'] = 'disabled';
        }
    }


    return $this->view->render($response, 'user_table_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/tables/give_permission/{usr_id}/{connection_id}/{table_name}', function (Request $request, Response $response, $args) use ($app) {


    R::exec("INSERT INTO permissions(prm_connection_id,prm_usr_id,prm_table_name) VALUES(:prm_connection_id,:prm_usr_id,:prm_table_name)", ['prm_connection_id' => $args['connection_id'], 'prm_usr_id' => $args['usr_id'], 'prm_table_name' => $args['table_name']]);


    return $response->withRedirect('/users/permissions/tables/' . $args['usr_id'] . '/' . $args['connection_id']);
})->add($admin_auth);


$app->get('/users/permissions/fields/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections");


    return $this->view->render($response, 'user_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/fields/give_permission/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $permissions = $request->getParam('permissions');
//    foreach ($permissions as $key => $permission) {
//        R::exec("DELETE FROM permissions WHERE prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id']]);
//        R::exec("INSERT INTO permissions (prm_id,prm_usr_id) VALUES (:prm_id,:prm_usr_id)", ['prm_id' => $key, 'prm_usr_id' => $args['usr_id']]);
//    }

    return $response->withRedirect('/users');
})->add($admin_auth);












