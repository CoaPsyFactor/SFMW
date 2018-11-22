<?php STPL::SectionContent('footer', function (float $queryTime = 0.0) { ?>
    <span>Copyright &copy; <?php echo date('Y', time()); ?></span> | Page query time: <?php echo $queryTime; ?>
<?php }); ?>