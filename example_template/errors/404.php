<?php

use Simple\Template;

// set template used as base for current view
Template::SetBase(__DIR__ . '/../base.php');

// set partials included with current view
Template::Partials([__DIR__ . '/../partials/top.php', __DIR__ . '/../partials/bottom.php']);

// section example  using pure php
Template::SectionContent('title', function () { echo 'STPL - Bad Input'; });

Template::SectionContent('content', function () {
    echo 'Snap! This page doesn\'t exists around here...';
});