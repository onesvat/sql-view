<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/connections', function (Request $request, Response $response, $args) use ($app) {
//
//    $args['connections'] = R::getAll("SELECT * FROM connections");
//
//    foreach ($args['connections'] as $key => $database) {
//
//        $connection_status = "success";
//
//        $conn_data = json_decode($database['cnn_connection'], true);
//
//        if ($database['cnn_type'] == "mysql") {
//
//            try {
//                new Connection(0, $request->getParam('cnn_type'), [
//                    'host' => $request->getParam('cnn_host'),
//                    'port' => $request->getParam('cnn_port'),
//                    'username' => $request->getParam('cnn_username'),
//                    'password' => $request->getParam('cnn_password'),
//                    'database' => $request->getParam('cnn_database')
//                ]);
//            } catch (Exception $e) {
//                return $response->withJson(['success' => false, 'message' => $e->getMessage()]);
//            }
//
//            $conn = @new mysqli($conn_data['cnn_host'], $conn_data['cnn_username'], $conn_data['cnn_password'], $conn_data['cnn_database']);
//
//            if ($conn->connect_error) {
//                $connection_status = "error";
//            }
//        }
//
//        $args['connections'][$key]['conn'] = $conn_data;
//        $args['connections'][$key]['connection_status'] = $connection_status;
//
//    }

    $connections = R::getAll("SELECT * FROM connections");

    foreach ($connections as &$connection) {
        $connection['cnn_settings'] = json_decode($connection['cnn_connection'], true);

        $connection['cnn_host'] = $connection['cnn_settings']['cnn_host'];
        $connection['cnn_database'] = $connection['cnn_settings']['cnn_database'];

        try {
            new Connection($connection);

            $connection['cnn_connection_status'] = true;
        } catch (Exception $e) {
            $connection['cnn_connection_status'] = false;
        }
    }

    $args['connections'] = $connections;

    return $this->view->render($response, 'connections.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->get('/connections/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'connections_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth)->add($admin_auth);

$app->post('/connections/new', function (Request $request, Response $response, $args) use ($app) {

    $cnn_connection = json_encode(
        [
            'cnn_username' => $request->getParam('cnn_username'),
            'cnn_password' => $request->getParam('cnn_password'),
            'cnn_host' => $request->getParam('cnn_host'),
            'cnn_port' => $request->getParam('cnn_port'),
            'cnn_database' => $request->getParam('cnn_database')
        ]);


    R::exec("INSERT INTO connections (cnn_name, cnn_type, cnn_connection, cnn_created_date, cnn_access_date) VALUES(:cnn_name, :cnn_type, :cnn_connection, :cnn_created_date, :cnn_access_date)", [
        'cnn_name' => $request->getParam('cnn_name'),
        'cnn_type' => $request->getParam('cnn_type'),
        'cnn_connection' => $cnn_connection,
        'cnn_created_date' => date('Y-m-d H:i:s'),
        'cnn_access_date' => null,
    ]);

    return $response->withRedirect('/connections');
})->add($user_auth)->add($admin_auth);

$app->get('/connections/edit/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {

    $args = R::getRow("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['cnn_id']]);
    $args = array_merge(json_decode($args['cnn_connection'], true), $args);
    return $this->view->render($response, 'connections_edit.html.twig', array_merge($app->extra, $args));

})->add($user_auth)->add($admin_auth);

$app->post('/connections/edit/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {


    $cnn_connection = json_encode(
        [
            'cnn_username' => $request->getParam('cnn_username'),
            'cnn_password' => $request->getParam('cnn_password'),
            'cnn_host' => $request->getParam('cnn_host'),
            'cnn_port' => $request->getParam('cnn_port'),
            'cnn_database' => $request->getParam('cnn_database')
        ]);


    R::exec("UPDATE connections SET cnn_user = :cnn_user, cnn_name = :cnn_name, cnn_type = :cnn_type, cnn_connection = :cnn_connection, cnn_created_date = :cnn_created_date, cnn_access_date = :cnn_access_date WHERE cnn_id = :cnn_id", [
        'cnn_user' => $_SESSION['usr_id'],
        'cnn_name' => $request->getParam('cnn_name'),
        'cnn_type' => $request->getParam('cnn_type'),
        'cnn_connection' => $cnn_connection,
        'cnn_created_date' => date('Y-m-d H:i:s'),
        'cnn_access_date' => null,
        'cnn_id' => $args['cnn_id'],
    ]);

    return $response->withRedirect("/connections");

})->add($user_auth)->add($admin_auth);

$app->get('/connections/remove/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {

    R::exec("DELETE FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['cnn_id']]);
    return $response->withRedirect("/connections");

})->add($user_auth)->add($admin_auth);

$app->post('/connections/check', function (Request $request, Response $response, $args) use ($app) {

    try {
        new Connection(0, $request->getParam('cnn_type'), [
            'host' => $request->getParam('cnn_host'),
            'port' => $request->getParam('cnn_port'),
            'username' => $request->getParam('cnn_username'),
            'password' => $request->getParam('cnn_password'),
            'database' => $request->getParam('cnn_database')
        ]);
    } catch (Exception $e) {
        return $response->withJson(['success' => false, 'message' => $e->getMessage()]);
    }

    return $response->withJson(['success' => true]);

})->add($user_auth);






