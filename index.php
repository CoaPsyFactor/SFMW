<?php

/**
 * TODO: DO NOT REPEAT YOURSELF!!!! MOVE DUPLICATE - REFACTOR SIMILAR !!!!
 * 
 */

require_once __DIR__ . '/STPL.php';
require_once __DIR__ . '/SCTL.php';

SCTL::Post('login', function (string $username, string $password) {

    /**
     * Username and password will be retrieved from $_POST :)
     */
    var_dump($username, $password);

    STPL::Render(__DIR__ . '/example_template/pages/login.php');
});

SCTL::Get('login', function (string $username = '') {

    STPL::Render(__DIR__ . '/example_template/pages/login.php', ['username' => $username]);
});

SCTL::RegisterErrorHandler(SCTL::STATUS_BADREQUEST, function () {

    STPL::Render(__DIR__ . '/example_template/errors/400.php');

    return true;
});

SCTL::RegisterErrorHandler(SCTL::STATUS_NOTFOUND, function () {

    STPL::Render(__DIR__ . '/example_template/errors/404.php');

    return true;
});


SCTL::Run($_GET['action'] ?? 'home');