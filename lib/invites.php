<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const INVITE_TTL_DAYS_DEFAULT = 7;

function invite_create(int $group_id, int $created_by_user_id, int $ttl_days = INVITE_TTL_DAYS_DEFAULT, int $max_uses = 0): string {
    $ttl_days = max(1, min(30, $ttl_days));
    $max_uses = max(0, min(100, $max_uses));
    $token   = bin2hex(random_bytes(24));
    $expires = date('Y-m-d H:i:s', time() + $ttl_days * 86400);
    db()->prepare(
        'INSERT INTO group_invites(token, group_id, created_by_user_id, expires_at, max_uses) VALUES(?,?,?,?,?)'
    )->execute([$token, $group_id, $created_by_user_id, $expires, $max_uses]);
    return $token;
}

function invite_get(string $token): array|false {
    $s = db()->prepare('SELECT * FROM group_invites WHERE token = ?');
    $s->execute([$token]);
    return $s->fetch();
}

// Returns ['error'=>...] or ['group_id'=>N, 'already_member'=>bool]
function invite_consume(string $token, int $user_id): array {
    $inv = invite_get($token);
    if (!$inv)                                 return ['error' => 'Enlace de invitación no válido.'];
    if ((int)$inv['revoked'] === 1)            return ['error' => 'Este enlace ha sido revocado.'];
    if ($inv['expires_at'] < date('Y-m-d H:i:s')) return ['error' => 'Este enlace ha caducado.'];
    if ((int)$inv['max_uses'] > 0 && (int)$inv['uses'] >= (int)$inv['max_uses']) {
        return ['error' => 'Este enlace ha alcanzado el límite de usos.'];
    }

    $gid = (int)$inv['group_id'];
    $db  = db();

    $chk = $db->prepare('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?');
    $chk->execute([$gid, $user_id]);
    if ($chk->fetch()) {
        return ['group_id' => $gid, 'already_member' => true];
    }

    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO group_members(group_id, user_id, role) VALUES(?,?,?)')
           ->execute([$gid, $user_id, 'member']);
        $db->prepare('UPDATE group_invites SET uses = uses + 1 WHERE token = ?')
           ->execute([$token]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return ['group_id' => $gid, 'already_member' => false];
}

function invite_list(int $group_id): array {
    $s = db()->prepare(
        "SELECT i.*, u.name AS created_by_name
         FROM group_invites i
         LEFT JOIN users u ON u.id = i.created_by_user_id
         WHERE i.group_id = ?
         ORDER BY i.created_at DESC"
    );
    $s->execute([$group_id]);
    return $s->fetchAll();
}

function invite_revoke(string $token, int $group_id): void {
    db()->prepare('UPDATE group_invites SET revoked=1 WHERE token=? AND group_id=?')
        ->execute([$token, $group_id]);
}

function invite_is_active(array $inv): bool {
    if ((int)$inv['revoked'] === 1) return false;
    if ($inv['expires_at'] < date('Y-m-d H:i:s')) return false;
    if ((int)$inv['max_uses'] > 0 && (int)$inv['uses'] >= (int)$inv['max_uses']) return false;
    return true;
}

function base_url(): string {
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    return $scheme . '://' . $host . $path . '/';
}
