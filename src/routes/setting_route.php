<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/setting', function (Request $request, Response $response, $args) use ($app) {
    $args['connections'] = R::getAll("SELECT * FROM connections");
    foreach ($args['connections'] as $key => $database) {
        $args['connections'][$key]['conn'] = json_decode($database['cnn_connection'], true);

    }

    return $this->view->render($response, 'setting.html.twig', array_merge($app->extra, $args));
})->add($user_auth);


$app->get('/setting/connection/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'setting_connection_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/setting/connection/new', function (Request $request, Response $response, $args) use ($app) {

    $cnn_connection = json_encode(
        [
            'cnn_username' => $request->getParam('cnn_username'),
            'cnn_password' => $request->getParam('cnn_password'),
            'cnn_host' => $request->getParam('cnn_host'),
            'cnn_port' => $request->getParam('cnn_port'),
            'cnn_database' => $request->getParam('cnn_database')
        ]);


    R::exec("INSERT INTO connections (cnn_user, cnn_name, cnn_type, cnn_connection, cnn_created_date, cnn_access_date) VALUES(:cnn_user, :cnn_name, :cnn_type, :cnn_connection, :cnn_created_date, :cnn_access_date)", [
        'cnn_user' => $_SESSION['usr_id'],
        'cnn_name' => $request->getParam('cnn_name'),
        'cnn_type' => $request->getParam('cnn_type'),
        'cnn_connection' => $cnn_connection,
        'cnn_created_date' => date('Y-m-d H:i:s'),
        'cnn_access_date' => null,
    ]);

    return $response->withRedirect('/setting');
})->add($user_auth);

$app->get('/setting/connection/edit/:cnn_id', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'setting_connection_edit.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/setting/connection/edit/:cnn_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);

$app->get('/setting/connection/delete/:cnn_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);


