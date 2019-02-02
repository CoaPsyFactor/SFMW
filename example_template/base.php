<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php use Simple\Template;

        Template::RenderSection('title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="container">
        <header class="header"><?php Template::RenderSection('header'); ?></header>
        <main class="main">
            <div class="sidebar"><?php Template::RenderSection('sidebar'); ?></div>
            <div class="content"><?php Template::RenderSection('content'); ?></div>
        </main>
        <footer class="footer"><?php Template::RenderSection('footer'); ?></footer>
    </div>
</body>
</html>