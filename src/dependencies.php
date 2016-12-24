<?php

// view renderer
$container['renderer'] = function ($container) {
    $settings = $container->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};


// Register component on container
$container['view'] = function ($container) {
    $settings = $container->get('settings')['renderer'];

    $view = new \Slim\Views\Twig($settings['template_path'], [
        //'cache' => $settings['cache_path']
    ]);

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    $view->addExtension(new Twig_Extensions_Extension_I18n());

    $view->getEnvironment()->getExtension('core')->setTimezone($_ENV['APP_TIMEZONE']);

    return $view;
};

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};
