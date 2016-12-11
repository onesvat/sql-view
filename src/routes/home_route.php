<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response, $args) use ($app) {
    return $response->withRedirect('/dashboard');
})->add($user_auth);


$app->get('/login', function (Request $request, Response $response, $args) use ($app) {
    return $this->view->render($response, 'login.html.twig', $args);
});

$app->post('/login', function (Request $request, Response $response, $args) use ($app) {

});

$app->get('/register', function (Request $request, Response $response, $args) use ($app) {

});

$app->post('/register', function (Request $request, Response $response, $args) use ($app) {

});

