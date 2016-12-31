<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/favorite', function (Request $request, Response $response, $args) use ($app) {


    return $this->view->render($response, 'favorite.html.twig', array_merge($app->extra, $args));
})->add($user_auth);
