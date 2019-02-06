<?php

return function (int $postId, bool $edit = false): array {
    $post = \Simple\Database::Fetch('SELECT * FROM `posts` WHERE `id` = :postId;', [':postId' => $postId]);

    return compact('post', 'edit');
};