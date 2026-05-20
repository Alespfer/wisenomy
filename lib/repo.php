<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/currency.php';

// --- Authorization helpers ---

function user_is_member(int $user_id, int $group_id): bool {
    $s = db()->prepare('SELECT 1 FROM group_members WHERE user_id=? AND group_id=?');
    $s->execute([$user_id, $group_id]);
    return (bool)$s->fetch();
}

function user_is_owner(int $user_id, int $group_id): bool {
    $s = db()->prepare('SELECT 1 FROM groups WHERE id=? AND owner_user_id=?');
    $s->execute([$group_id, $user_id]);
    return (bool)$s->fetch();
}

// --- Groups (scoped by user) ---

function group_list(int $user_id): array {
    $s = db()->prepare(
        'SELECT g.*, gm.role AS my_role
         FROM groups g
         JOIN group_members gm ON gm.group_id = g.id
         WHERE gm.user_id = ?
         ORDER BY g.created_at DESC'
    );
    $s->execute([$user_id]);
    return $s->fetchAll();
}

function group_get(int $id): array|false {
    $s = db()->prepare('SELECT * FROM groups WHERE id=?');
    $s->execute([$id]);
    return $s->fetch();
}

function group_create(string $name, string $currency, int $user_id): int {
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO groups(name, base_currency, owner_user_id) VALUES(?,?,?)')
           ->execute([trim($name), strtoupper($currency), $user_id]);
        $gid = last_insert_id('groups');
        $db->prepare('INSERT INTO group_members(group_id, user_id, role) VALUES(?,?,?)')
           ->execute([$gid, $user_id, 'owner']);
        $db->commit();
        return $gid;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function group_update(int $id, string $name, string $currency): void {
    db()->prepare('UPDATE groups SET name=?,base_currency=? WHERE id=?')
        ->execute([trim($name), strtoupper($currency), $id]);
}

function group_delete(int $id): void {
    db()->prepare('DELETE FROM groups WHERE id=?')->execute([$id]);
}

// Transfers ownership to another existing member. Returns null on success or error message.
function group_transfer_ownership(int $group_id, int $current_owner_id, int $new_owner_id): ?string {
    if ($current_owner_id === $new_owner_id) return 'Ya eres el propietario.';
    if (!user_is_owner($current_owner_id, $group_id)) return 'Solo el propietario puede transferir el grupo.';
    if (!user_is_member($new_owner_id, $group_id))    return 'El nuevo propietario debe ser miembro del grupo.';

    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE groups SET owner_user_id=? WHERE id=?')
           ->execute([$new_owner_id, $group_id]);
        $db->prepare("UPDATE group_members SET role='member' WHERE group_id=? AND user_id=?")
           ->execute([$group_id, $current_owner_id]);
        $db->prepare("UPDATE group_members SET role='owner' WHERE group_id=? AND user_id=?")
           ->execute([$group_id, $new_owner_id]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return null;
}

// --- Statistics ---

// Builds WHERE clause fragment + params for the date range filter.
// Returns ['sql' => string starting with ' AND ...', 'params' => [...]]
function stats_date_clause(array $filters): array {
    $sql = '';
    $params = [];
    if (!empty($filters['date_from'])) {
        $sql .= ' AND occurred_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= ' AND occurred_at <= ?';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    return ['sql' => $sql, 'params' => $params];
}

function stats_totals(int $group_id, array $filters = []): array {
    $d = stats_date_clause($filters);
    $s = db()->prepare(
        "SELECT
           COUNT(*) AS tx_count,
           COALESCE(SUM(CASE WHEN type IN ('expense_equal','expense_shares','expense_full') THEN amount_base_cents ELSE 0 END), 0) AS expense_cents,
           COALESCE(SUM(CASE WHEN type = 'gift' THEN amount_base_cents ELSE 0 END), 0) AS gift_cents,
           COALESCE(SUM(CASE WHEN type = 'settlement' THEN amount_base_cents ELSE 0 END), 0) AS settlement_cents
         FROM transactions WHERE group_id = ?" . $d['sql']
    );
    $s->execute(array_merge([$group_id], $d['params']));
    return $s->fetch() ?: ['tx_count' => 0, 'expense_cents' => 0, 'gift_cents' => 0, 'settlement_cents' => 0];
}

function stats_by_category(int $group_id, array $filters = []): array {
    $d = stats_date_clause($filters);
    $s = db()->prepare(
        "SELECT
           CASE WHEN category = '' THEN '(sin categoría)' ELSE category END AS category,
           COUNT(*) AS n,
           SUM(amount_base_cents) AS total_cents
         FROM transactions
         WHERE group_id = ? AND type IN ('expense_equal','expense_shares','expense_full')" . $d['sql'] . "
         GROUP BY category
         ORDER BY total_cents DESC"
    );
    $s->execute(array_merge([$group_id], $d['params']));
    return $s->fetchAll();
}

function stats_by_payer(int $group_id, array $filters = []): array {
    $d = stats_date_clause($filters);
    // Date clause references occurred_at — need to alias in t. context
    $date_sql = str_replace('occurred_at', 't.occurred_at', $d['sql']);
    $s = db()->prepare(
        "SELECT p.name AS payer,
                COUNT(*) AS n,
                SUM(t.amount_base_cents) AS total_cents
         FROM transactions t
         JOIN participants p ON p.id = t.payer_participant_id
         WHERE t.group_id = ? AND t.type IN ('expense_equal','expense_shares','expense_full')" . $date_sql . "
         GROUP BY p.id, p.name
         ORDER BY total_cents DESC"
    );
    $s->execute(array_merge([$group_id], $d['params']));
    return $s->fetchAll();
}

function stats_by_month(int $group_id, array $filters = []): array {
    $d = stats_date_clause($filters);
    $s = db()->prepare(
        "SELECT substr(occurred_at, 1, 7) AS month,
                COUNT(*) AS n,
                SUM(amount_base_cents) AS total_cents
         FROM transactions
         WHERE group_id = ? AND type IN ('expense_equal','expense_shares','expense_full')" . $d['sql'] . "
         GROUP BY month
         ORDER BY month DESC"
    );
    $s->execute(array_merge([$group_id], $d['params']));
    return $s->fetchAll();
}

// --- Group memberships ---

function group_member_list(int $group_id): array {
    $s = db()->prepare(
        'SELECT u.id, u.email, u.name, gm.role, gm.joined_at
         FROM group_members gm
         JOIN users u ON u.id = gm.user_id
         WHERE gm.group_id = ?
         ORDER BY gm.role DESC, u.name'
    );
    $s->execute([$group_id]);
    return $s->fetchAll();
}

function group_member_add_by_email(int $group_id, string $email): array {
    $email = strtolower(trim($email));
    $s = db()->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    if (!$u) return ['error' => 'No existe usuario registrado con ese email.'];
    if (user_is_member((int)$u['id'], $group_id)) return ['error' => 'Ese usuario ya es miembro.'];
    db()->prepare('INSERT INTO group_members(group_id, user_id, role) VALUES(?,?,?)')
        ->execute([$group_id, (int)$u['id'], 'member']);
    return ['ok' => true];
}

function group_member_remove(int $group_id, int $user_id): void {
    db()->prepare("DELETE FROM group_members WHERE group_id=? AND user_id=? AND role <> 'owner'")
        ->execute([$group_id, $user_id]);
}

// --- Participants ---

function participant_list(int $group_id): array {
    $s = db()->prepare('SELECT * FROM participants WHERE group_id=? ORDER BY name');
    $s->execute([$group_id]);
    return $s->fetchAll();
}

function participant_get(int $id): array|false {
    $s = db()->prepare('SELECT * FROM participants WHERE id=?');
    $s->execute([$id]);
    return $s->fetch();
}

function participant_create(int $group_id, string $name): int {
    $s = db()->prepare('INSERT INTO participants(group_id,name) VALUES(?,?)');
    $s->execute([$group_id, trim($name)]);
    return last_insert_id('participants');
}

function participant_update(int $id, string $name): void {
    db()->prepare('UPDATE participants SET name=? WHERE id=?')->execute([trim($name), $id]);
}

function participant_delete(int $id): void {
    db()->prepare('DELETE FROM participants WHERE id=?')->execute([$id]);
}

// --- Transactions ---

function tx_list(int $group_id, array $filters = []): array {
    // Whitelist sortable columns (no user input reaches ORDER BY directly)
    $sort_map = [
        'occurred_at'       => 't.occurred_at',
        'category'          => 't.category',
        'amount_base_cents' => 't.amount_base_cents',
        'payer'             => 'p.name',
        'type'              => 't.type',
    ];
    $sort = $sort_map[$filters['sort'] ?? ''] ?? null;
    $dir  = (($filters['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';

    $sql = 'SELECT t.*, p.name AS payer_name
            FROM transactions t
            JOIN participants p ON p.id = t.payer_participant_id
            WHERE t.group_id = ?';
    $params = [$group_id];

    if (!empty($filters['payer'])) {
        $sql .= ' AND p.name LIKE ?';
        $params[] = '%' . $filters['payer'] . '%';
    }
    if (!empty($filters['date_from'])) {
        $sql .= ' AND t.occurred_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= ' AND t.occurred_at <= ?';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    if ($sort !== null) {
        $sql .= " ORDER BY $sort $dir, t.id DESC";
    } else {
        $sql .= ' ORDER BY t.occurred_at DESC, t.id DESC';
    }

    $s = db()->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function tx_get(int $id): array|false {
    $s = db()->prepare(
        'SELECT t.*, p.name AS payer_name
         FROM transactions t
         JOIN participants p ON p.id = t.payer_participant_id
         WHERE t.id=?'
    );
    $s->execute([$id]);
    return $s->fetch();
}

function tx_shares(int $tx_id): array {
    $s = db()->prepare(
        'SELECT ts.*, p.name AS participant_name
         FROM transaction_shares ts
         JOIN participants p ON p.id = ts.participant_id
         WHERE ts.transaction_id=?'
    );
    $s->execute([$tx_id]);
    return $s->fetchAll();
}

function tx_create(array $d): int {
    $group = group_get($d['group_id']);
    $fx    = fx_convert((int)$d['amount_cents'], $d['currency'], $group['base_currency']);

    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare(
            'INSERT INTO transactions
             (group_id,type,payer_participant_id,amount_cents,currency,
              amount_base_cents,fx_rate,fx_rate_at,fx_stale,category,note,occurred_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $d['group_id'],
            $d['type'],
            $d['payer_participant_id'],
            $d['amount_cents'],
            $d['currency'],
            $fx['cents'],
            $fx['rate'],
            $fx['fetched_at'],
            $fx['stale'] ? 1 : 0,
            $d['category'] ?? '',
            $d['note'] ?? '',
            $d['occurred_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $tx_id = last_insert_id('transactions');

        tx_insert_shares($db, $tx_id, $d, $fx['cents']);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return $tx_id;
}

function tx_update(int $tx_id, array $d): void {
    $group = group_get($d['group_id']);
    $fx    = fx_convert((int)$d['amount_cents'], $d['currency'], $group['base_currency']);

    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare(
            'UPDATE transactions SET
             type=?,payer_participant_id=?,amount_cents=?,currency=?,
             amount_base_cents=?,fx_rate=?,fx_rate_at=?,fx_stale=?,
             category=?,note=?,occurred_at=?
             WHERE id=?'
        )->execute([
            $d['type'],
            $d['payer_participant_id'],
            $d['amount_cents'],
            $d['currency'],
            $fx['cents'],
            $fx['rate'],
            $fx['fetched_at'],
            $fx['stale'] ? 1 : 0,
            $d['category'] ?? '',
            $d['note'] ?? '',
            $d['occurred_at'] ?? date('Y-m-d H:i:s'),
            $tx_id,
        ]);

        $db->prepare('DELETE FROM transaction_shares WHERE transaction_id=?')->execute([$tx_id]);
        tx_insert_shares($db, $tx_id, $d, $fx['cents']);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function tx_delete(int $id): void {
    db()->prepare('DELETE FROM transactions WHERE id=?')->execute([$id]);
}

function tx_insert_shares(PDO $db, int $tx_id, array $d, int $amount_base_cents): void {
    $type = $d['type'];

    if ($type === 'gift') {
        return; // no shares, no debt
    }

    $ins = $db->prepare('INSERT INTO transaction_shares(transaction_id,participant_id,share_cents) VALUES(?,?,?)');

    if ($type === 'expense_equal') {
        $ids   = $d['participant_ids'] ?? [];
        $n     = count($ids);
        if ($n === 0) return;
        $base  = intdiv($amount_base_cents, $n);
        $rem   = $amount_base_cents - $base * $n;
        foreach ($ids as $i => $pid) {
            $ins->execute([$tx_id, (int)$pid, $base + ($i === 0 ? $rem : 0)]);
        }
    } elseif ($type === 'expense_full') {
        $ins->execute([$tx_id, (int)$d['beneficiary_id'], $amount_base_cents]);
    } elseif ($type === 'expense_shares') {
        // shares passed as ['participant_id' => int, 'share_cents' => int][]
        // share_cents are in original currency; convert proportionally
        $raw_shares    = $d['shares'] ?? [];
        $raw_total     = array_sum(array_column($raw_shares, 'share_cents'));
        foreach ($raw_shares as $sh) {
            $proportional = ($raw_total > 0)
                ? (int)round($amount_base_cents * $sh['share_cents'] / $raw_total)
                : 0;
            $ins->execute([$tx_id, (int)$sh['participant_id'], $proportional]);
        }
    } elseif ($type === 'settlement') {
        // payer pays amount to beneficiary → beneficiary share = amount (reduces debt)
        $ins->execute([$tx_id, (int)$d['beneficiary_id'], $amount_base_cents]);
    }
}
