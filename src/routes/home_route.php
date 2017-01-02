<?php

use RedBeanPHP\R;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $response->withRedirect('/dashboard');
})->add($user_auth);

$app->get('/connection/change/{cnn_id}', function (Request $request, Response $response, $args) use ($app) {
    R::exec("REPLACE INTO active_connections SET atc_usr_id = :usr_id, atc_active_cnn_id = :cnn_id", ['usr_id' => $app->extra['user']['usr_id'], 'cnn_id' => $args['cnn_id']]);
    return $response->withRedirect($_SERVER['HTTP_REFERER']);
})->add($user_auth);

$app->get('/login', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $this->view->render($response, 'login.html.twig', array_merge($app->extra, $args));
});

$app->post('/login', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $user = R::getRow("SELECT * FROM users WHERE usr_email = :usr_email AND usr_password = :usr_password", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    if ($user) {
        $_SESSION['login'] = true;
        $_SESSION['usr_id'] = $user['usr_id'];
        $_SESSION['usr_type'] = $user['usr_type'];

        return $response->withRedirect('/dashboard');
    }

    $this->flash->addMessage('danger', "Your email or password is wrong");

    return $response->withRedirect('/login');

});

$app->get('/setup', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    $super_admin = R::getCell("SELECT set_value FROM settings WHERE set_key = 'super_admin'");

    if ($super_admin) {
        $notFoundHandler = $this->get('notFoundHandler');
        return $notFoundHandler($request, $response);
    }

    return $this->view->render($response, 'setup.html.twig', array_merge($app->extra, $args));
});

$app->post('/setup', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $password_check = $request->getParam('password_check');

    if ($password != $password_check) {
        $this->flash->addMessage('danger', "Passwords does not match");

        return $response->withRedirect('/register');
    }

    R::exec("INSERT INTO users (usr_type, usr_email, usr_password) VALUES ('admin', :usr_email, :usr_password)", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    $usr_id = R::getInsertID();

    R::exec("INSERT INTO settings (set_key, set_value) VALUES (:set_key, :set_value)", ['set_key' => 'super_admin', 'set_value' => $usr_id]);

    $this->flash->addMessage('success', "You may login now");

    return $response->withRedirect('/login');
});

$app->get('/logout', function (Request $request, Response $response, $args) {
    session_destroy();

    return $response->withRedirect("/");
});

