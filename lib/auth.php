<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Returns null if password meets the policy, otherwise an error message.
// Policy: >=8 chars, at least 1 lowercase, 1 uppercase, 1 digit, 1 special.
function password_policy_check(string $pw): ?string {
    if (strlen($pw) < 8)                 return 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[a-z]/',     $pw)) return 'Falta una letra minúscula.';
    if (!preg_match('/[A-Z]/',     $pw)) return 'Falta una letra mayúscula.';
    if (!preg_match('/[0-9]/',     $pw)) return 'Falta un número.';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) return 'Falta un carácter especial (p. ej. !@#$%).';
    return null;
}

function user_register(string $email, string $password, string $name): array {
    $email = strtolower(trim($email));
    $name  = trim($name);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Email inválido.'];
    }
    if (($pw_err = password_policy_check($password)) !== null) {
        return ['error' => $pw_err];
    }
    if ($name === '') {
        return ['error' => 'Indica tu nombre.'];
    }

    $exists = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        return ['error' => 'Ya existe una cuenta con ese email.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    db()->prepare('INSERT INTO users(email, password_hash, name) VALUES(?,?,?)')
        ->execute([$email, $hash, $name]);

    return ['user_id' => (int)db()->lastInsertId()];
}

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MIN   = 15;

function login_attempts_recent(string $email, string $ip): int {
    $win = LOGIN_WINDOW_MIN;
    $s = db()->prepare(
        "SELECT COUNT(*) c FROM login_attempts
         WHERE success = 0
           AND (email = ? OR ip = ?)
           AND attempted_at >= datetime('now', '-{$win} minutes')"
    );
    $s->execute([$email, $ip]);
    return (int)$s->fetch()['c'];
}

function login_attempt_log(string $email, string $ip, bool $success): void {
    db()->prepare('INSERT INTO login_attempts(email, ip, success) VALUES(?,?,?)')
        ->execute([$email, $ip, $success ? 1 : 0]);
    // Prune old rows opportunistically (keep DB small)
    db()->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-1 day')");
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function user_login(string $email, string $password): array {
    $email = strtolower(trim($email));
    $ip    = client_ip();

    if (login_attempts_recent($email, $ip) >= LOGIN_MAX_ATTEMPTS) {
        return ['error' => 'Demasiados intentos. Espera ' . LOGIN_WINDOW_MIN . ' minutos.'];
    }

    $s = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
        login_attempt_log($email, $ip, false);
        return ['error' => 'Credenciales incorrectas.'];
    }

    login_attempt_log($email, $ip, true);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    // Refresh CSRF token after privilege change
    unset($_SESSION['csrf']);
    return ['user_id' => (int)$u['id']];
}

// --- Profile updates ---

function user_update_name(int $user_id, string $name): ?string {
    $name = trim($name);
    if ($name === '')              return 'El nombre no puede estar vacío.';
    if (strlen($name) > 80)        return 'El nombre es demasiado largo (máx. 80).';
    db()->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $user_id]);
    return null;
}

function user_change_password(int $user_id, string $current_pw, string $new_pw): ?string {
    $s = db()->prepare('SELECT password_hash FROM users WHERE id=?');
    $s->execute([$user_id]);
    $u = $s->fetch();
    if (!$u || !password_verify($current_pw, $u['password_hash'])) {
        return 'La contraseña actual no es correcta.';
    }
    if (($pw_err = password_policy_check($new_pw)) !== null) return $pw_err;
    if (password_verify($new_pw, $u['password_hash']))       return 'La nueva contraseña debe ser distinta de la actual.';

    $hash = password_hash($new_pw, PASSWORD_BCRYPT);
    db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $user_id]);
    // Invalidate any pending reset tokens for safety
    db()->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$user_id]);
    return null;
}

// --- Account deletion ---

// Returns ['owned_groups'=>N, 'owned_txs'=>M, 'shared_groups'=>P] for confirmation summary
function user_deletion_summary(int $user_id): array {
    $db = db();
    $a = $db->prepare('SELECT COUNT(*) c FROM groups WHERE owner_user_id = ?');
    $a->execute([$user_id]); $owned_groups = (int)$a->fetch()['c'];

    $b = $db->prepare(
        'SELECT COUNT(*) c FROM transactions t
         JOIN groups g ON g.id = t.group_id
         WHERE g.owner_user_id = ?'
    );
    $b->execute([$user_id]); $owned_txs = (int)$b->fetch()['c'];

    $c = $db->prepare(
        "SELECT COUNT(*) c FROM group_members gm
         JOIN groups g ON g.id = gm.group_id
         WHERE gm.user_id = ? AND g.owner_user_id <> ?"
    );
    $c->execute([$user_id, $user_id]); $shared_groups = (int)$c->fetch()['c'];

    return [
        'owned_groups'  => $owned_groups,
        'owned_txs'     => $owned_txs,
        'shared_groups' => $shared_groups,
    ];
}

// Verifies password + deletes user. Cascade does the rest. Returns null on success or error message.
function user_delete(int $user_id, string $password): ?string {
    $s = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $s->execute([$user_id]);
    $u = $s->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        return 'Contraseña incorrecta.';
    }
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);
    return null;
}

// --- Password reset ---

const RESET_TTL_HOURS = 1;

function password_reset_create(string $email): ?string {
    $email = strtolower(trim($email));
    $s = db()->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    if (!$u) return null;

    // Invalidate previous unused tokens for this user
    db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([(int)$u['id']]);

    $token   = bin2hex(random_bytes(32));
    $ttl     = RESET_TTL_HOURS;
    $expires = date('Y-m-d H:i:s', time() + $ttl * 3600);
    db()->prepare('INSERT INTO password_resets(token, user_id, expires_at) VALUES(?,?,?)')
        ->execute([$token, (int)$u['id'], $expires]);

    // Prune old expired tokens
    db()->exec("DELETE FROM password_resets WHERE expires_at < datetime('now')");

    return $token;
}

function password_reset_get_user(string $token): ?int {
    $s = db()->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > datetime('now')");
    $s->execute([$token]);
    $r = $s->fetch();
    return $r ? (int)$r['user_id'] : null;
}

function password_reset_consume(string $token, string $new_password): array {
    if (($pw_err = password_policy_check($new_password)) !== null) {
        return ['error' => $pw_err];
    }
    $uid = password_reset_get_user($token);
    if ($uid === null) return ['error' => 'El enlace de recuperación no es válido o ha expirado.'];

    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $uid]);
    db()->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
    return ['user_id' => $uid];
}

// Records the reset link to a file for now. In production, replace with SMTP send.
// Timestamp uses ISO-8601 with offset so UTC vs local timezone is unambiguous.
function password_reset_deliver(string $email, string $token, string $base_url): void {
    $link = $base_url . '?action=reset&token=' . $token;
    $log  = __DIR__ . '/../data/password_resets.log';
    $line = date('c') . " | $email | $link\n"; // e.g. 2026-05-20T13:01:50+00:00
    file_put_contents($log, $line, FILE_APPEND | LOCK_EX);
}

// --- CSRF ---

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    return !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)($_POST['_csrf'] ?? ''));
}

function user_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $cache = null;
    if ($cache !== null && $cache['id'] === $_SESSION['user_id']) return $cache;

    $s = db()->prepare('SELECT id, email, name, created_at FROM users WHERE id = ?');
    $s->execute([$_SESSION['user_id']]);
    $u = $s->fetch();
    if (!$u) { user_logout(); return null; }
    $cache = $u;
    return $u;
}

function require_auth(): void {
    if (!current_user()) {
        header('Location: ?action=login');
        exit;
    }
}
