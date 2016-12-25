<?php
use \RedBeanPHP\R;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/database', function (Request $request, Response $response, $args) use ($app) {

    $args['databases'] = R::getAll("SELECT * FROM `databases`");
    foreach ($args['databases'] as $key => $database) {
        $args['databases'][$key]['conn'] = json_decode($database['dtb_connection'], true);

    }

    return $this->view->render($response, 'database.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->get('/database/new', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'database_new.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/database/new', function (Request $request, Response $response, $args) use ($app) {

    $dtb_connection = json_encode(
        [
            'con_username' => $request->getParam('con_username'),
            'con_password' => $request->getParam('con_password'),
            'con_host' => $request->getParam('con_host'),
            'con_port' => $request->getParam('con_port'),
        ]);


    R::exec("INSERT INTO `databases` (`dtb_user`, `dtb_name`, `dtb_type`, `dtb_connection`, `dtb_created_date`) VALUES(:dtb_user,:dtb_name,:dtb_type,:dtb_connection,:dtb_created_date)", [
        'dtb_user' => $_SESSION['usr_id'],
        'dtb_name' => $request->getParam('dtb_name'),
        'dtb_type' => $request->getParam('dtb_type'),
        'dtb_connection' => $dtb_connection,
        'dtb_created_date' => date('Y-m-d H:i:s')
    ]);

    return $response->withRedirect('/database');
})->add($user_auth);

$app->get('/database/edit/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

    return $this->view->render($response, 'database_edit.html.twig', array_merge($app->extra, $args));
})->add($user_auth);

$app->post('/database/edit/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);

$app->get('/database/delete/:dtb_id', function (Request $request, Response $response, $args) use ($app) {

})->add($user_auth);


