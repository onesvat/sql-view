<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/dashboard', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'dashboard.html.twig', array_merge($app->extra, $args));
})->add($user_auth);
