<?php

class Application extends \Slim\App
{
    public $extra = [];
    public $messages = [];

    public $timezone = "UTC";

    /** @var  Connection */
    public $connection;
}