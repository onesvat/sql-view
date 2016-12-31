<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/query', function (Request $request, Response $response, $args) use ($app) {


    $tables = $app->connection->getFields();

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

    return $this->view->render($response, 'query.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/query/run', function (Request $request, Response $response, $args) use ($app) {
    $query_string = $request->getParam('query');
    $cache = $request->getParam('cache');

    $query = new Query($query_string, $app->connection);
    $result = $query->getArray($cache);
    return $response->withJson($result, 200);
})->add($user_auth);

$app->get('/query/{query_hash}.{download_type}', function (Request $request, Response $response, $args) use ($app) {

    $query_string = R::getCell("SELECT que_string FROM queries WHERE que_hash = :que_hash", ['que_hash' => $args['query_hash']]);

    if (!$query_string) {
        return $response->withStatus(400);
    }

    if ($request->getParam('d')) {
        header("Content-Disposition: attachment;filename={$args['query_hash']}.{$args['download_type']}");
    }

    $query = new Query($query_string, $app->connection);
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

})->add($user_auth);





