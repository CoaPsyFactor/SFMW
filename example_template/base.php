<!DOCTYPE <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php STPL::RenderSection('title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="container">
        <header class="header"><?php STPL::RenderSection('header'); ?></header>
        <main class="main">
            <div class="sidebar"><?php STPL::RenderSection('sidebar'); ?></div>
            <div class="content"><?php STPL::RenderSection('content'); ?></div>
        </main>
        <footer class="footer"><?php STPL::RenderSection('footer'); ?></footer>
    </div>
</body>
</html>