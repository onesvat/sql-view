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

$app->post('/query', function (Request $request, Response $response, $args) use ($app) {

});

$app->get('/query/cached', function (Request $request, Response $response, $args) use ($app) {

});

$app->post('/query/run', function (Request $request, Response $response, $args) use ($app) {

    $query_string = $request->getParam('query');
    $query = new Query($query_string, $app->connection);
    $result = $query->getArray();
    return $response->withJson($result, 200);
})->add($user_auth);

$app->post('/query/download/{query_hash}.json', function (Request $request, Response $response, $args) use ($app) {

    $query_string = $request->getParam('query');
    $query = new Query($query_string, $app->connection);
    $result = $query->getArray();
    return $response->withJson($result, 200);
})->add($user_auth);





