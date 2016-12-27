<?php

use Slim\Http\Request;
use Slim\Http\Response;
use RedBeanPHP\R;

$app->get('/setting', function (Request $request, Response $response, $args) use ($app) {


    $args['connections'] = R::getAll("SELECT * FROM connections");
    foreach ($args['connections'] as $key => $database) {

        $connection_status = "success";

        $conn_data = json_decode($database['cnn_connection'], true);

        if ($database['cnn_type'] == "mysql") {

            if (!empty($conn_data['cnn_port'])) {

                $conn_data['cnn_host'] .= ':' . $conn_data['cnn_port'];
            }

            $conn = @new mysqli($conn_data['cnn_host'], $conn_data['cnn_username'], $conn_data['cnn_password'], $conn_data['cnn_database']);

            if ($conn->connect_error) {
                $connection_status = "error";
            }
        }

        $args['connections'][$key]['conn'] = $conn_data;
        $args['connections'][$key]['connection_status'] = $connection_status;

    }

    return $this->view->render($response, 'setting.html.twig', array_merge($app->extra, $args));
})->add($user_auth);


$app->get('/setting/connection/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'setting_connection_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/setting/connection/new', function (Request $request, Response $response, $args) use ($app) {

    $cnn_connection = json_encode(
        [
            'cnn_username' => $request->getParam('cnn_username'),
            'cnn_password' => $request->getParam('cnn_password'),
            'cnn_host' => $request->getParam('cnn_host'),
            'cnn_port' => $request->getParam('cnn_port'),
            'cnn_database' => $request->getParam('cnn_database')
        ]);


    R::exec("INSERT INTO connections (cnn_user, cnn_name, cnn_type, cnn_connection, cnn_created_date, cnn_access_date) VALUES(:cnn_user, :cnn_name, :cnn_type, :cnn_connection, :cnn_created_date, :cnn_access_date)", [
        'cnn_user' => $_SESSION['usr_id'],
        'cnn_name' => $request->getParam('cnn_name'),
        'cnn_type' => $request->getParam('cnn_type'),
        'cnn_connection' => $cnn_connection,
        'cnn_created_date' => date('Y-m-d H:i:s'),
        'cnn_access_date' => null,
    ]);

    return $response->withRedirect('/setting');
})->add($user_auth);

$app->get('/setting/connection/edit/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {

    $args = R::getRow("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['cnn_id']]);
    $args = json_decode($args['cnn_connection'], true);
    return $this->view->render($response, 'setting_connection_edit.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/setting/connection/edit/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {


})->add($user_auth);

$app->get('/setting/connection/remove/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {

    R::exec("DELETE FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $args['cnn_id']]);
    R::exec("UPDATE connections SET cnn_status = 'active' WHERE cnn_user = :cnn_user LIMIT 1", ['cnn_user' => $_SESSION['usr_id']]);
    return $response->withRedirect("/setting");

})->add($user_auth);

$app->get('/setting/connection/change/{connection_id}', function (Request $request, Response $response, $args) use ($app) {

    R::getAll("UPDATE connections SET cnn_status = 'passive' WHERE cnn_user = :cnn_user", ['cnn_user' => $_SESSION['usr_id']]);
    R::getAll("UPDATE connections SET cnn_status = 'active' WHERE cnn_id = :cnn_id AND cnn_user = :cnn_user", ['cnn_user' => $_SESSION['usr_id'], 'cnn_id' => $args['connection_id']]);

    return $response->withRedirect("/dashboard");

})->add($user_auth);




