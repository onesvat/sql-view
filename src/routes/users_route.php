<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/users', function (Request $request, Response $response, $args) use ($app) {
    $args['users'] = R::getAll("SELECT * FROM users");

    return $this->view->render($response, 'users.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->get('/users/permissions/connections/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections");


    return $this->view->render($response, 'user_connection_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->post('/users/permissions/connections/{usr_id}', function (Request $request, Response $response, $args) use ($app) {
    $permissions = $request->getParam('permissions');

    var_dump($permissions);
    die;
    foreach ($permissions as $key => $permission) {
        R::exec("DELETE FROM permissions WHERE prm_connection_id = :prm_connection_id AND prm_usr_id = :prm_usr_id", ['prm_connection_id' => $key, 'prm_usr_id' => $args['usr_id']]);
        R::exec("INSERT INTO permissions (prm_usr_id,prm_connection_id) VALUES (:prm_usr_id,:prm_connection_id)", ['prm_connection_id' => $key, 'prm_usr_id' => $args['usr_id']]);
    }

    return $response->withRedirect('/users');
})->add($admin_auth);


$app->get('/users/permissions/tables/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections");

    return $this->view->render($response, 'user_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);

$app->post('/users/permissions/tables/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $permissions = $request->getParam('permissions');
    foreach ($permissions as $key => $permission) {
        R::exec("DELETE FROM permissions WHERE prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id']]);
        R::exec("INSERT INTO permissions (prm_id,prm_usr_id) VALUES (:prm_id,:prm_usr_id)", ['prm_id' => $key, 'prm_usr_id' => $args['usr_id']]);
    }

    return $response->withRedirect('/users');
})->add($admin_auth);


$app->get('/users/permissions/fields/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $args['connections'] = R::getAll("SELECT * FROM connections");


    return $this->view->render($response, 'user_permissions.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);


$app->post('/users/permissions/fields/{usr_id}', function (Request $request, Response $response, $args) use ($app) {

    $permissions = $request->getParam('permissions');
    foreach ($permissions as $key => $permission) {
        R::exec("DELETE FROM permissions WHERE prm_usr_id = :prm_usr_id", ['prm_usr_id' => $args['usr_id']]);
        R::exec("INSERT INTO permissions (prm_id,prm_usr_id) VALUES (:prm_id,:prm_usr_id)", ['prm_id' => $key, 'prm_usr_id' => $args['usr_id']]);
    }

    return $response->withRedirect('/users');
})->add($admin_auth);







