<?php

use Simple\Database;
use Simple\StatusCode;

return function (int $postId, array $post): array {
    $user = $_SESSION['user'] ?? null;

    if (false === ($user['id'] ?? false)) {
        return ['status' => StatusCode::UNAUTHENTICATED, 'message' => 'You must be logged in.', 'error' => true];
    }

    ['title' => $title, 'content' => $content] = $post;

    $post = Database::Fetch('
          SELECT `p`.*, `u`.`id` as `user_id` FROM `posts` as p LEFT JOIN `users` as u ON `u`.`id` = `p`.`user_id` WHERE `p`.`id` = :postId LIMIT 1;',
        [':postId' => $postId]
    );

    if (empty($post)) {
        return ['status' => StatusCode::NOT_FOUND, 'message' => 'Post not found.', 'error' => true];
    }

    if ((int)$post['user_id'] !== $user['id']) {
        return ['status' => StatusCode::FORBIDDEN, 'message' => 'You don\'t have permission to edit this post.', 'error' => true];
    }

    Database::Store('UPDATE `posts` SET `title` = :title, `content` = :content WHERE `id` = :postId;', [
        ':title' => htmlspecialchars($title),
        ':content' => htmlspecialchars($content),
        ':postId' => (int)$post['id'],
    ]);

    return compact('postId');
};