<?php

use Simple\Template;

return function (array $data) {
    Template::Render(__DIR__ . '/../views/pages/error/generic.phtml', $data);
};