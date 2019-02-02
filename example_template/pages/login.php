<!-- set template used as base for current view-->
<?php use Simple\Template;

Template::SetBase(__DIR__ . '/../base.php'); ?>

<!-- set partials included with current view -->
<?php Template::Partials([__DIR__ . '/../partials/top.php', __DIR__ . '/../partials/bottom.php']); ?>

<!-- section example  using pure php -->
<?php Template::SectionContent('title', function () { echo 'STPL - Login Page'; }); ?>

<!-- section example using pure php combined with plain html -->
<?php Template::SectionContent('content', function (string $username = '') { ?>
    <form method="post" action="?action=login">
        <input type="text" name="username" placeholder="Username" value="<?php echo $username; ?>" />
        <input type="password" name="password" placeholder="Password" />
        <button type="submit">Login</button>
    </form>
<?php }); ?>
