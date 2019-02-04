<?php

use Simple\Database;
use Simple\Framework;

require_once __DIR__ . '/vendor/autoload.php';

Framework::Initialize([
    'database' => [
        'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
        'user' => 'root', 'password' => '', 'schema' => 'retroad']
]);

Framework::On('home', function () {
    $users = Database::fetch('SELECT * FROM `users` WHERE `username` = :username', [':username' => 'coa']);

    var_dump($users);
});

Framework::Catch(RuntimeException::class, function (RuntimeException $exception) {
    echo 'Got error ' . $exception->getMessage();
});

Framework::Trigger(
    filter_input(INPUT_GET, 'route', FILTER_DEFAULT, ['options' => ['default' => 'home']])
);