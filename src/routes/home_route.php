<?php

use RedBeanPHP\R;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $response->withRedirect('/dashboard');
})->add($user_auth);


$app->get('/login', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $this->view->render($response, 'login.html.twig', array_merge($app->extra, $args));
});

$app->post('/login', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $usr_id = R::getCell("SELECT usr_id FROM users WHERE usr_status = 'active' AND usr_email = :usr_email AND usr_password = :usr_password AND usr_type != 'admin'", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    if ($usr_id) {
        $_SESSION['login'] = true;
        $_SESSION['usr_id'] = $usr_id;

        return $response->withRedirect('/dashboard');
    }

    $this->flash->addMessage('danger', "Your email or password is wrong");

    return $response->withRedirect('/login');

});

$app->get('/admin', function (Request $request, Response $response, $args) use ($app) {

    $check_admin = R::getRow("SELECT * FROM users WHERE usr_type = 'admin'");

    if (empty($check_admin)) {
        return $this->view->render($response, 'admin_register.html.twig', array_merge($app->extra, $args));
        die;
    }
    return $this->view->render($response, 'admin_login.html.twig', array_merge($app->extra, $args));
});


$app->post('/admin', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $usr_id = R::getCell("SELECT usr_id FROM users WHERE usr_status = 'active' AND usr_email = :usr_email AND usr_password = :usr_password", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    if ($usr_id) {
        $_SESSION['admin'] = true;
        $_SESSION['usr_id'] = $usr_id;

        return $response->withRedirect('/dashboard');
    }

    $this->flash->addMessage('danger', "Your email or password is wrong");

    return $response->withRedirect('/admin');

});


$app->get('/admin_register', function (Request $request, Response $response, $args) use ($app) {
    $app->extra['messages'] = $this->flash->getMessages();

    return $this->view->render($response, 'admin_register.html.twig', array_merge($app->extra, $args));
});

$app->post('/admin_register', function (Request $request, Response $response, $args) use ($app) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $password_check = $request->getParam('password_check');

    if ($password != $password_check) {
        $this->flash->addMessage('danger', "Passwords does not match");

        return $response->withRedirect('/register');
    }

    R::exec("INSERT INTO users (usr_status,usr_type, usr_email, usr_password) VALUES ('active','admin', :usr_email, :usr_password)", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    $this->flash->addMessage('success', "You may login now");

    return $response->withRedirect('/login');
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

    R::exec("INSERT INTO users (usr_status, usr_email, usr_password) VALUES ('active', :usr_email, :usr_password)", [
        'usr_email' => $email,
        'usr_password' => md5($password)
    ]);

    $this->flash->addMessage('success', "You may login now");

    return $response->withRedirect('/login');
});


$app->get('/logout', function (Request $request, Response $response, $args) {
    $_SESSION['login'] = false;
    $_SESSION['admin'] = false;
    $_SESSION['usr_id'] = -1;

    return $response->withRedirect("/");
});