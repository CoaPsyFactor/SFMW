<?php

/**
 * TODO: DO NOT REPEAT YOURSELF!!!! MOVE DUPLICATE - REFACTOR SIMILAR !!!!
 * 
 */

//require_once __DIR__ . '/STPL.php';
//require_once __DIR__ . '/SCTL.php';
use Simple\Controller;
use Simple\Template;

require_once __DIR__ . '/vendor/autoload.php';

Controller::Post('login', function (string $username, string $password) {

    /**
     * Username and password will be retrieved from $_POST :)
     */
    var_dump($username, $password);

    Template::Render(__DIR__ . '/example_template/pages/login.php');
});

Controller::Get('login', function (string $username = '') {
    Template::Render(__DIR__ . '/example_template/pages/login.php', ['username' => $username]);
});

Controller::RegisterErrorHandler(Controller::STATUS_BAD_REQUEST, function () {
    Template::Render(__DIR__ . '/example_template/errors/400.php');

    return true;
});

Controller::RegisterErrorHandler(Controller::STATUS_NOT_FOUND, function () {
    Template::Render(__DIR__ . '/example_template/errors/404.php');

    return true;
});


Controller::Run($_GET['action'] ?? 'home');
