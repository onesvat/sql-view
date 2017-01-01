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

$app->get('/users/permission/{usr_id}', function (Request $request, Response $response, $args) use ($app) {


    $connections = Connection::getConnectionsWithPermissions($args['usr_id']);


    foreach ($connections as &$connection) {
        $settings = json_decode($connection['cnn_connection'], true);

        $connection['cnn_host'] = $settings['cnn_host'];
        $connection['cnn_database'] = $settings['cnn_database'];

        try {
            new Connection(0, $connection['cnn_type'], [
                'host' => $settings['cnn_host'],
                'port' => $settings['cnn_port'],
                'username' => $settings['cnn_username'],
                'password' => $settings['cnn_password'],
                'database' => $settings['cnn_database']
            ]);

            $connection['cnn_connection_status'] = true;
        } catch (Exception $e) {
            $connection['cnn_connection_status'] = false;
        }
    }

    $args['connections'] = $connections;

    return $this->view->render($response, 'users_permission.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->get('/users/permission/set/{usr_id}', function (Request $request, Response $response, $args) use ($app) {
    $usr_id = $args['usr_id'];
    $cnn_id = $request->getParam('cnn_id');
    $permission = $request->getParam('permission');

    if ($permission == "full" || $permission == "none") {
        R::exec("REPLACE INTO permissions SET prm_user = :usr_id, prm_connection = :cnn_id, prm_permission_type = :permission", ['usr_id' => $usr_id, 'cnn_id' => $cnn_id, 'permission' => $permission]);
        return $response->withRedirect("/users/permission/{$usr_id}");
    }

    $prm_permission = R::getCell("SELECT prm_permission FROM permissions WHERE prm_user = :usr_id AND prm_connection = :cnn_id", ['usr_id' => $usr_id, 'cnn_id' => $cnn_id]);

    if ($prm_permission) {
        $permissions = json_decode($prm_permission, true);
    } else {
        $permissions = [];
    }


    $tables = Connection::getConnectionFromId($cnn_id)->getFields();

    $tree = [];

    foreach ($tables as $table) {
        $columns = [];

        $expanded = false;

        foreach ($table['columns'] as $column) {
            if (array_key_exists($table['table_name'], $permissions) && in_array($column['column_name'], $permissions[$table['table_name']])) {
                $checked = true;
                $expanded = true;
            } else {
                $checked = false;
            }



            $columns[] = ['type' => 'column', 'table_name' => $table['table_name'], 'column_name' => $column['column_name'], "text" => $column['column_name'] . " - <i>" . $column['column_data_type'] . "</i>", 'state' => ['checked' => $checked, 'selectable' => false]];
        }

        $tree[] = ['type' => 'table', 'table_name' => $table['table_name'], "text" => $table['table_name'], 'nodes' => $columns, 'state' => ['expanded' => $expanded, 'selectable' => false]];
    }


    $args['tree'] = json_encode($tree);
    $args['connection'] = R::getRow("SELECT * FROM connections WHERE cnn_id = :cnn_id", [':cnn_id' => $cnn_id]);
    $args['usr_id'] = $usr_id;
    $args['cnn_id'] = $cnn_id;


    return $this->view->render($response, 'users_permission_detailed.html.twig', array_merge($app->extra, $args));

})->add($user_auth)->add($admin_auth);

$app->post('/users/permission/set/partial/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $usr_id = $args['usr_id'];
    $cnn_id = $request->getParam('cnn_id');

    $permission = $request->getParam('data');

    R::exec("REPLACE INTO permissions SET prm_user = :usr_id, prm_connection = :cnn_id, prm_permission_type = :permission_type, prm_permission = :permission", ['usr_id' => $usr_id, 'cnn_id' => $cnn_id, 'permission_type' => 'partial', 'permission' => $permission]);

})->add($user_auth)->add($admin_auth);
