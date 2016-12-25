<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/database', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'database.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->get('/database/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'database_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/database/new', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);

$app->get('/database/edit/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'database_edit.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/database/edit/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);

$app->get('/database/delete/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);