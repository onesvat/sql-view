<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/query', function (Request $request, Response $response, $args) use ($app) {

    $connection = new Connection($app->extra['active_connection'], $app->extra['user']);

    $tables = $connection->getFields();

    $tree = [];

    foreach ($tables as $table) {
        $columns = [];

        foreach ($table['columns'] as $column) {
            $columns[] = ["text" => $column['column_name'] . " - <i>" . $column['column_data_type'] . "</i>", 'column_name' => $column['column_name']];
        }

        $tree[] = ["text" => $table['table_name'], 'nodes' => $columns, 'state' => ['expanded' => false]];
    }

    $args['tables'] = $tables;
    $args['tree'] = json_encode($tree);
    $args['permission'] = $connection->getPermissionType();

    return $this->view->render($response, 'query.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/query/run', function (Request $request, Response $response, $args) use ($app) {
    $query_string = $request->getParam('query');
    $cache = $request->getParam('cache');

    $query = new Query($query_string, new Connection($app->extra['active_connection']), $app->extra['user']);
    $result = $query->getArray($cache);
    return $response->withJson($result, 200);
})->add($user_auth);

$app->post('/query/run/gui', function (Request $request, Response $response, $args) use ($app) {
    $tables = $request->getParam('tables');
    $fields = $request->getParam('fields');
    $builder = $request->getParam('builder');

    $query_string = "SELECT " . implode(",", $fields) . " FROM " . implode(',', $tables);

    if($builder['sql']) {
        $query_string .= " WHERE " . $builder['sql'];
    }

    $cache = $request->getParam('cache');

    $query = new Query($query_string, new Connection($app->extra['active_connection']), $app->extra['user']);
    $result = $query->getArray($cache);
    return $response->withJson($result, 200);
})->add($user_auth);


$app->post('/query/get_fields', function (Request $request, Response $response, $args) use ($app) {
    $selected_tables = $request->getParam('tables');

    if (count($selected_tables) == 0)
        return $response->withJson(['data' => [], 'filter' => []]);


    $data = [];
    $filter = [];

    $connection = new Connection($app->extra['active_connection'], $app->extra['user']);

    $tables = $connection->getFields();

    foreach ($tables as $table) {
        foreach ($table['columns'] as $column) {
            if (in_array($table['table_name'], $selected_tables)) {
                $data[] = ["id" => $table['table_name'] . "." . $column['column_name'], "text" => $table['table_name'] . "." . $column['column_name']];
                $filter[] = ['id' => $table['table_name'] . "." . $column['column_name'], 'label' => $table['table_name'] . "." . $column['column_name']];
            }
        }
    }


    return $response->withJson(['data' => $data, 'filter' => $filter]);
})->add($user_auth);

$app->get('/query/{query_hash}.{download_type}', function (Request $request, Response $response, $args) use ($app) {

    $_query = R::getRow("SELECT * FROM queries WHERE que_hash = :que_hash", ['que_hash' => $args['query_hash']]);

    if (!$_query) {
        return $response->withStatus(400);
    }

    if ($request->getParam('d')) {
        header("Content-Disposition: attachment;filename={$args['query_hash']}.{$args['download_type']}");
    }

    $user = R::getRow("SELECT * FROM users WHERE usr_id = :que_user", ['que_user' => $_query['que_user']]);
    $connection = R::getRow("SELECT * FROM connections WHERE cnn_id = :que_connection", ['que_connection' => $_query['que_connection']]);

    $connection['cnn_settings'] = json_decode($connection['cnn_connection'], true);

    $query = new Query($_query['que_string'], new Connection($connection, $user), $user);
    $result = $query->getArray(true);

    if ($args['download_type'] == "json") {
        header('Content-type:  application/json');
        echo json_encode($result);
    } else if ($args['download_type'] == "csv") {
        header("Content-Type: text/csv");
        $out = fopen('php://output', 'w');
        fputcsv($out, $result['columns']);
        foreach ($result['rows'] as $row) {
            fputcsv($out, $row, ",", '"');
        }
        fclose($out);

    } else if ($args['download_type'] == "tsv") {
        header('Content-type: text/tab-separated-values');
        echo implode("\t", $result['columns']);
        foreach ($result['rows'] as $row) {
            echo implode("\t", $row);
            echo "\n";
        }
    } else {
        return $response->withStatus(400);
    }

});




