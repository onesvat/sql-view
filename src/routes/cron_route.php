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

$app->get('/load', function (Request $request, Response $response, $args) use ($app) {
    $products = R::getAll("SELECT * FROM products");
    $customers = R::getAll("SELECT * FROM customers");
    $products_count = count($products);
    $customers_count = count($customers);

    $sql = "";

    for ($i = 0; $i < 10000000; $i++) {
        $products_id = $products[mt_rand(0, $products_count - 1)]['id'];
        $customers_id = $customers[mt_rand(0, $customers_count - 1)]['id'];
        $amount = mt_rand(1, 3);
        $sale_datetime = date("Y-m-d H:i:s", mt_rand(strtotime("2010-01-01"), time()));

        $sql .= "INSERT INTO sales SET products_id = $products_id, customers_id = $customers_id, amount = $amount, sale_datetime = {$sale_datetime};";
    }

    file_put_contents("/tmp/estore.sql", $sql);
});