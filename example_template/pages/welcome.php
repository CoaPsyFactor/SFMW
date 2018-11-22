<!-- set template used as base for current view-->
<?php STPL::SetBase(__DIR__ . '/../base.php'); ?>

<!-- set partials included with current view -->
<?php STPL::Partials([__DIR__ . '/../partials/top.php', __DIR__ . '/../partials/bottom.php']); ?>

<!-- section example  using pure php -->
<?php STPL::SectionContent('title', function () { echo 'STPL - Welcome Page'; }); ?>

<!-- section example using pure php combined with plain html -->
<?php STPL::SectionContent('content', function (array $user) { ?>
    <div>Hello there, <b><?php echo "{$user['firstName']} {$user['lastName']}"; ?></div></b>
<?php }); ?>
