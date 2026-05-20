<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Records an action in the group activity feed. user_name is denormalized so
// the entry remains readable even after the user is deleted (FK ON DELETE SET NULL).
function activity_log(int $group_id, ?int $user_id, string $user_name, string $action, string $summary): void {
    db()->prepare(
        'INSERT INTO activity_log(group_id, user_id, user_name, action, summary) VALUES(?,?,?,?,?)'
    )->execute([$group_id, $user_id, $user_name, $action, $summary]);
}

function activity_list(int $group_id, int $limit = 50, int $offset = 0): array {
    $limit  = max(1, min(200, $limit));
    $offset = max(0, $offset);
    $s = db()->prepare(
        "SELECT * FROM activity_log
         WHERE group_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT $limit OFFSET $offset"
    );
    $s->execute([$group_id]);
    return $s->fetchAll();
}

function activity_count(int $group_id): int {
    $s = db()->prepare('SELECT COUNT(*) c FROM activity_log WHERE group_id = ?');
    $s->execute([$group_id]);
    return (int)$s->fetch()['c'];
}
