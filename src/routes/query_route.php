<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/query', function (Request $request, Response $response, $args) use ($app) {

    $stmt = $app->connection->prepare("SELECT TABLE_NAME table_name, COLUMN_NAME column_name, COLUMN_TYPE column_type, DATA_TYPE column_data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='adjo-panel'");
    $stmt->execute();
    $fields = $stmt->fetchAll();

    $tables = [];
    foreach ($fields as $field) {
        if (!array_key_exists($field['table_name'], $tables)) {
            $tables[$field['table_name']] = ['table_name' => $field['table_name'], 'columns' => []];
        }

        $tables[$field['table_name']]['columns'][] = ['column_name' => $field['column_name'], 'column_type' => $field['column_type'], 'column_data_type' => $field['column_data_type']];
    }


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
    $args['queries'] = R::getAll("SELECT * FROM queries");

    return $this->view->render($response, 'query.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/query', function (Request $request, Response $response, $args) use ($app) {

});

$app->get('/query/cached', function (Request $request, Response $response, $args) use ($app) {

});