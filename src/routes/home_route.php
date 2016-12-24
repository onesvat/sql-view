<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $response->withRedirect('/dashboard', array_merge($app->extra, $args));
})->add($user_auth);


$app->get('/login', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $this->view->render($response, 'login.html.twig', array_merge($app->extra, $args));
});

$app->post('/login', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $user_id = R::getCell("SELECT id FROM users WHERE status = 'active' AND email = :email AND password = :password", [
        'email' => $email,
        'password' => md5($password)
    ]);

    if ($user_id) {
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $user_id;
    } else {
        $this->flash->addMessage('danger', "Your email or password is wrong");

        return $response->withRedirect('/login');
    }
});

$app->get('/register', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $this->view->render($response, 'register.html.twig', array_merge($app->extra, $args));
});

$app->post('/register', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $password_check = $request->getParam('password_check');

    if ($password != $password_check) {
        $this->flash->addMessage('danger', "Passwords does not match");

        return $response->withRedirect('/register');
    }

    R::exec("INSERT INTO users (status, email, password) VALUES ('active', :email, :password)", [
        'email' => $email,
        'password' => md5($password)
    ]);

    $this->flash->addMessage('success', "You may login now");

    return $response->withRedirect('/login');
});

