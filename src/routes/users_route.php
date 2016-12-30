<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/users', function (Request $request, Response $response, $args) use ($app) {

    $args['users'] = R::getAll("SELECT * FROM users");


    return $this->view->render($response, 'users.html.twig', array_merge($app->extra, $args));
})->add($admin_auth);
