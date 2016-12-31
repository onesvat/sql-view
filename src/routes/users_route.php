<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/users', function (Request $request, Response $response, $args) use ($app) {

    $args['users'] = R::getAll("SELECT * FROM users");

    return $this->view->render($response, 'users.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->get('/users/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'users_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->post('/users/new', function (Request $request, Response $response, $args) use ($app) {

    R::exec("INSERT INTO users (usr_type, usr_email, usr_password) VALUES (:usr_type, :usr_email, :usr_password)", [
        'usr_type' => $request->getParam('usr_type'),
        'usr_email' => $request->getParam('usr_email'),
        'usr_password' => md5($request->getParam('password'))
    ]);

    return $response->withRedirect('/users');
})->add($user_auth)->add($admin_auth);

$app->get('/users/edit/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['curr_user'] = R::getRow("SELECT * FROM users WHERE usr_id = :usr_id", ['usr_id' => $args['usr_id']]);

    return $this->view->render($response, 'users_edit.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->post('/users/edit/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    R::exec("UPDATE users SET usr_type = :usr_type, usr_email = :usr_email, usr_password = :usr_password WHERE usr_id = :usr_id", [
        'usr_id' => $args['usr_id'],
        'usr_type' => $request->getParam('usr_type'),
        'usr_email' => $request->getParam('usr_email'),
        'usr_password' => md5($request->getParam('password'))
    ]);

    return $response->withRedirect('/users');
})->add($user_auth)->add($admin_auth);


$app->get('/users/permissions/connections/{usr_id}', function (Request $request, Response $response, $args) use ($app) {



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
})->add($user_auth)->add($admin_auth);


$app->get('/users/connection/give_permission/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $connection_id = $args['connection_id'];

    R::exec("DELETE FROM permissions WHERE prm_connection_id = :prm_connection_id AND prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $connection_id]);

    R::exec("INSERT INTO permissions(prm_connection_id,prm_usr_id) VALUES(:prm_connection_id,:prm_usr_id)", ['prm_connection_id' => $connection_id, 'prm_usr_id' => $args['usr_id']]);

    return $response->withRedirect('/users/permissions/connections/' . $args['usr_id']);


})->add($user_auth)->add($admin_auth);


$app->get('/users/connection/remove_permission/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $connection_id = $args['connection_id'];

    R::exec("DELETE FROM permissions WHERE prm_connection_id = :prm_connection_id AND prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $connection_id]);

    return $response->withRedirect('/users/permissions/connections/' . $args['usr_id']);


})->add($user_auth)->add($admin_auth);


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
})->add($user_auth)->add($admin_auth);


$app->get('/users/tables/give_permission/{usr_id}/{connection_id}/{table_name}', function (Request $request, Response $response, $args) use ($app) {


    R::exec("INSERT INTO permissions(prm_connection_id,prm_usr_id,prm_table_name) VALUES(:prm_connection_id,:prm_usr_id,:prm_table_name)", ['prm_connection_id' => $args['connection_id'], 'prm_usr_id' => $args['usr_id'], 'prm_table_name' => $args['table_name']]);


    return $response->withRedirect('/users/permissions/tables/' . $args['usr_id'] . '/' . $args['connection_id']);
})->add($user_auth)->add($admin_auth);


$app->get('/users/tables/remove_permission/{usr_id}/{connection_id}/{table_name}', function (Request $request, Response $response, $args) use ($app) {


    R::exec("DELETE FROM permissions WHERE prm_connection_id= :prm_connection_id AND prm_usr_id= :prm_usr_id AND prm_table_name= :prm_table_name", ['prm_connection_id' => $args['connection_id'], 'prm_usr_id' => $args['usr_id'], 'prm_table_name' => $args['table_name']]);


    return $response->withRedirect('/users/permissions/tables/' . $args['usr_id'] . '/' . $args['connection_id']);
})->add($user_auth)->add($admin_auth);


$app->get('/users/permissions/fields/{usr_id}/{connection_id}/{table_name}', function (Request $request, Response $response, $args) use ($app) {


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

        $stmt = $app->connection->prepare("DESCRIBE {$args['table_name']}");
        $stmt->execute();

        $args['fields'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        echo 'Connection failed' . $e->getMessage();
        die;
    }


    foreach ($args['fields'] as $key => $field_name) {
        $table_permission = R::getRow("SELECT * FROM permissions WHERE prm_usr_id = :prm_usr_id AND prm_connection_id = :prm_connection_id AND prm_table_name = :prm_table_name AND prm_field_name = :prm_field_name", ['prm_usr_id' => $args['usr_id'], 'prm_connection_id' => $args['connection_id'], 'prm_table_name' => $args['table_name'], 'prm_field_name' => $field_name]);
        if (empty($table_permission)) {
            $args['field_permission'][$key]['give_permission_button'] = 'enabled';
        } else {
            $args['field_permission'][$key]['give_permission_button'] = 'disabled';
        }
    }


    return $this->view->render($response, 'user_field_permissions.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);


$app->get('/users/fields/give_permission/{usr_id}/{connection_id}/{table_name}/{field_name}', function (Request $request, Response $response, $args) use ($app) {


    R::exec("INSERT INTO permissions(prm_connection_id,prm_usr_id,prm_table_name,prm_field_name) VALUES(:prm_connection_id,:prm_usr_id,:prm_table_name,:prm_field_name)", ['prm_connection_id' => $args['connection_id'], 'prm_usr_id' => $args['usr_id'], 'prm_table_name' => $args['table_name'], 'prm_field_name' => $args['field_name']]);


    return $response->withRedirect('/users/permissions/fields/' . $args['usr_id'] . '/' . $args['connection_id'] . '/' . $args['table_name']);

})->add($admin_auth);


$app->get('/users/fields/remove_permission/{usr_id}/{connection_id}/{table_name}/{field_name}', function (Request $request, Response $response, $args) use ($app) {


    R::exec("DELETE FROM permissions WHERE prm_connection_id= :prm_connection_id AND prm_usr_id= :prm_usr_id AND prm_table_name= :prm_table_name AND prm_field_name = :prm_field_name", ['prm_connection_id' => $args['connection_id'], 'prm_usr_id' => $args['usr_id'], 'prm_table_name' => $args['table_name'], 'prm_field_name' => $args['field_name']]);


    return $response->withRedirect('/users/permissions/fields/' . $args['usr_id'] . '/' . $args['connection_id'] . '/' . $args['table_name']);
})->add($user_auth)->add($admin_auth);














