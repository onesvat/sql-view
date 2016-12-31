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

    $app->extra['active_connection'] = R::getAll("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['connection_id']]);
    try {

        var_dump($app->extra['active_connection']);

        $stmt = $app->connection->prepare("SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name, COLUMN_TYPE  as column_type, DATA_TYPE  as column_data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA= :table_schema");
        $stmt->execute(['table_schema' => $app->extra['active_connection']['connection']['cnn_database']]);
        $fields = $stmt->fetchAll();

        $tables = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field['table_name'], $tables)) {
                $tables[$field['table_name']] = ['table_name' => $field['table_name'], 'columns' => []];
            }

            $tables[$field['table_name']]['columns'][] = ['column_name' => $field['column_name'], 'column_type' => $field['column_type'], 'column_data_type' => $field['column_data_type']];
        }


        $tree = [];

        foreach ($tables as $table) {
            $columns = [];

            foreach ($table['columns'] as $column) {
                $columns[] = ["text" => $column['column_name'] . " - <i>" . $column['column_data_type'] . "</i>", 'column_name' => $column['column_name']];
            }

            $tree[] = ["text" => $table['table_name'], 'nodes' => $columns, 'state' => ['expanded' => false]];
        }

    } catch (Exception $e) {
        echo 'Connection failed' . $e->getMessage();
        die;
    }


    return $this->view->render($response, 'user_table_permissions.html.twig', array_merge($app->extra, $args, $tables));
})->add($admin_auth);


$app->get('/users/permissions/fields/{usr_id}/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections");


    return $this->view->render($response, 'user_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/tables/give_permission/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $permissions = $request->getParam('permissions');
//    foreach ($permissions as $key => $permission) {
//        R::exec("DELETE FROM permissions WHERE prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id']]);
//        R::exec("INSERT INTO permissions (prm_id,prm_usr_id) VALUES (:prm_id,:prm_usr_id)", ['prm_id' => $key, 'prm_usr_id' => $args['usr_id']]);
//    }

    return $response->withRedirect('/users');
})->add($admin_auth);


$app->get('/users/fields/give_permission/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $permissions = $request->getParam('permissions');
//    foreach ($permissions as $key => $permission) {
//        R::exec("DELETE FROM permissions WHERE prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id']]);
//        R::exec("INSERT INTO permissions (prm_id,prm_usr_id) VALUES (:prm_id,:prm_usr_id)", ['prm_id' => $key, 'prm_usr_id' => $args['usr_id']]);
//    }

    return $response->withRedirect('/users');
})->add($admin_auth);












