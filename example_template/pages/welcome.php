<!-- set template used as base for current view-->
<?php use Simple\Template;

Template::SetBase(__DIR__ . '/../base.php'); ?>

<!-- set partials included with current view -->
<?php Template::Partials([__DIR__ . '/../partials/top.php', __DIR__ . '/../partials/bottom.php']); ?>

<!-- section example  using pure php -->
<?php Template::SectionContent('title', function () { echo 'STPL - Welcome Page'; }); ?>

<!-- section example using pure php combined with plain html -->
<?php Template::SectionContent('content', function (array $user) { ?>
    <div>Hello there, <b><?php echo "{$user['firstName']} {$user['lastName']}"; ?></div></b>
<?php }); ?>
