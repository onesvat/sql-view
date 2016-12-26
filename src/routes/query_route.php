<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/query', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'query.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/query', function (Request $request, Response $response, $args) use ($app) {

});

$app->get('/query/cached', function (Request $request, Response $response, $args) use ($app) {

});