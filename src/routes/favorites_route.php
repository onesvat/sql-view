<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/favorites', function (Request $request, Response $response, $args) use ($app) {

    $logs = R::getAll("SELECT * FROM queries LEFT JOIN connections ON connections.cnn_id = queries.que_connection WHERE que_user = :usr_id AND que_favorite = 'no'", ['usr_id' => $app->extra['user']['usr_id']]);
    $favorites = R::getAll("SELECT * FROM queries LEFT JOIN connections ON connections.cnn_id = queries.que_connection WHERE que_user = :usr_id AND que_favorite = 'yes'", ['usr_id' => $app->extra['user']['usr_id']]);

    $args['logs'] = $logs;
    $args['favorites'] = $favorites;

    return $this->view->render($response, 'favorites.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/favorites/add', function (Request $request, Response $response, $args) use ($app) {
    R::exec("UPDATE queries SET que_favorite = 'yes' WHERE que_hash = :que_hash", ['que_hash' => $request->getParam('query_hash')]);
})->add($user_auth);

$app->post('/favorites/remove', function (Request $request, Response $response, $args) use ($app) {
    R::exec("UPDATE queries SET que_favorite = 'no' WHERE que_hash = :que_hash", ['que_hash' => $request->getParam('query_hash')]);
})->add($user_auth);

$app->post('/favorites/cache', function (Request $request, Response $response, $args) use ($app) {
    R::exec("UPDATE queries SET que_cache = :que_cache WHERE que_hash = :que_hash", ['que_hash' => $request->getParam('query_hash'), 'que_cache' => $request->getParam('cache')]);
})->add($user_auth);

