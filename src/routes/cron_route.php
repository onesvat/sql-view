<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/cron', function (Request $request, Response $response, $args) use ($app) {
    $queries = R::getAll("SELECT * FROM queries LEFT JOIN connections ON connections.cnn_id = queries.que_connection WHERE que_favorite = 'yes' AND que_cache > 0");

    $now = time();
    $today = strtotime(date("Y-m-d 00:00:00"));


    $diff = (int)($now - $today) / 60;


    foreach ($queries as $query) {
        $user = R::getRow("SELECT * FROM users WHERE usr_id = :que_user", ['que_user' => $query['que_user']]);
        $settings = json_decode($query['cnn_connection'], true);
        $que_cache = (int)($query['que_cache'] / 60);

        if ($diff % $que_cache == 0) {
            $connection = new Connection([
                'cnn_id' => $query['cnn_id'],
                'cnn_type' => $query['cnn_type'],
                'cnn_settings' => [
                    'cnn_host' => $settings['cnn_host'],
                    'cnn_port' => $settings['cnn_port'],
                    'cnn_username' => $settings['cnn_username'],
                    'cnn_password' => $settings['cnn_password'],
                    'cnn_database' => $settings['cnn_database']
                ]
            ], $user);

            $query = new Query($query['que_string'], $connection, $user);
            $query->getArray(false);
        }

    }
});