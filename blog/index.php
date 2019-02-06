<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Simple\Framework;
use Simple\StatusCode;

Framework::Initialize([
    'framework' => [
        'pageIdentifier' => 'page',
        'rootDirectory' => __DIR__ . '/src'
    ],
    'database' => [
        'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
        'user' => 'root', 'password' => '', 'schema' => 'retroad']
]);


Framework::RegisterPage('post', '/views/pages/post/index.phtml', '/controllers/post/view.php');
Framework::RegisterControl('post_update', '/controllers/post/edit.php', 'post');

Framework::RegisterErrorPage(
    StatusCode::FORBIDDEN, require_once __DIR__ . '/src/views/pages/error/generic.phtml'
);

Framework::Catch(RuntimeException::class, function (RuntimeException $exception) {
    echo 'Got error ' . $exception->getMessage();
});