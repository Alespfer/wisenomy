<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/config.php';

// Short-circuit /favicon.ico requests (browsers ask for it implicitly).
// Modern clients use favicon.svg via the <link> tag in the layout.
if (($_SERVER['REQUEST_URI'] ?? '') === '/favicon.ico') {
    http_response_code(204);
    exit;
}

// ── PRODUCTION HARDENING ───────────────────────────────────────────────────
if (is_production()) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL);

// ── SECURITY HEADERS ────────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "font-src 'self' https://cdn.jsdelivr.net; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "img-src 'self' data:; "
    . "connect-src 'self' https://api.frankfurter.dev; "
    . "form-action 'self'; "
    . "base-uri 'self'; "
    . "frame-ancestors 'none'"
);
if (request_is_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── SECURE SESSION COOKIE ───────────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => request_is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/currency.php';
require_once __DIR__ . '/lib/repo.php';
require_once __DIR__ . '/lib/settlement.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csv.php';
require_once __DIR__ . '/lib/invites.php';
require_once __DIR__ . '/lib/activity.php';

// ── CSRF GUARD on every POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    http_response_code(419);
    die('Token CSRF inválido. Vuelve atrás y recarga la página.');
}

$action = $_REQUEST['action'] ?? '';
$gid    = isset($_REQUEST['g'])  ? (int)$_REQUEST['g']  : 0;
$id     = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// ── helpers ──────────────────────────────────────────────────────────────────

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES);
}
function post(string $k, string $default = ''): string {
    return trim((string)($_POST[$k] ?? $default));
}
function dollars_to_cents(string $val): int {
    return (int)round((float)str_replace(',', '.', $val) * 100);
}
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function require_group_access(int $user_id, int $group_id): void {
    if (!user_is_member($user_id, $group_id)) {
        http_response_code(403);
        flash('error', 'No tienes acceso a este grupo.');
        redirect('?');
    }
}
function require_group_owner(int $user_id, int $group_id): void {
    if (!user_is_owner($user_id, $group_id)) {
        http_response_code(403);
        flash('error', 'Solo el propietario puede realizar esta acción.');
        redirect("?g=$group_id");
    }
}

// ── PUBLIC LEGAL PAGES (no auth) ────────────────────────────────────────────
if ($action === 'legal')   { require __DIR__ . '/views/legal.php';   exit; }
if ($action === 'privacy') { require __DIR__ . '/views/privacy.php'; exit; }
if ($action === 'terms')   { require __DIR__ . '/views/terms.php';   exit; }

// ── AUTH ROUTES (public) ────────────────────────────────────────────────────

if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = post('email');
        $r = user_register($email, $_POST['password'] ?? '', post('name'));
        if (isset($r['error'])) {
            $error = $r['error']; $name = post('name');
            require __DIR__ . '/views/register.php'; exit;
        }
        // Send verification email (uses Resend in prod, log file in dev)
        email_verification_send((int)$r['user_id'], strtolower(trim($email)), base_url());
        $verification_sent = true;
        $sent_email        = $email;
        require __DIR__ . '/views/register.php'; exit;
    }
    require __DIR__ . '/views/register.php'; exit;
}

// Email verification landing
if ($action === 'verify') {
    $token = (string)($_REQUEST['token'] ?? '');
    $uid   = $token !== '' ? email_verification_consume($token) : null;
    $verify_ok = $uid !== null;
    require __DIR__ . '/views/verify_email.php'; exit;
}

// Re-send verification email
if ($action === 'resend_verification' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(post('email')));
    if ($email !== '') {
        $s = db()->prepare('SELECT id, email_verified_at FROM users WHERE email = ?');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && empty($u['email_verified_at'])) {
            email_verification_send((int)$u['id'], $email, base_url());
        }
    }
    flash('success', 'Si el email existe y aún no está verificado, te hemos enviado un nuevo enlace.');
    redirect('?action=login');
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $r = user_login(post('email'), $_POST['password'] ?? '');
        if (isset($r['error'])) {
            $error = $r['error']; $email = post('email');
            $unverified = !empty($r['unverified_user']);
            require __DIR__ . '/views/login.php'; exit;
        }
        flash('success', 'Sesión iniciada.');
        redirect(!empty($_SESSION['pending_invite']) ? '?action=invite_accept' : '?');
    }
    require __DIR__ . '/views/login.php'; exit;
}

if ($action === 'forgot') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = post('email');
        if ($email === '') {
            $error = 'Indica un email.'; require __DIR__ . '/views/forgot.php'; exit;
        }
        $token = password_reset_create($email);
        // Always show same message regardless of whether email exists (anti-enumeration)
        if ($token !== null) {
            password_reset_deliver($email, $token, base_url());
        }
        $sent = true;
        require __DIR__ . '/views/forgot.php'; exit;
    }
    require __DIR__ . '/views/forgot.php'; exit;
}

if ($action === 'reset') {
    $token       = (string)($_REQUEST['token'] ?? '');
    $valid_token = $token !== '' && password_reset_get_user($token) !== null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $r = password_reset_consume($token, $_POST['password'] ?? '');
        if (isset($r['error'])) {
            $error = $r['error'];
            require __DIR__ . '/views/reset.php'; exit;
        }
        flash('success', 'Contraseña actualizada. Inicia sesión con la nueva.');
        redirect('?action=login');
    }
    require __DIR__ . '/views/reset.php'; exit;
}

// Invite landing: stores token in session and routes to login/register/accept
if ($action === 'invite') {
    $token = (string)($_REQUEST['token'] ?? '');
    $inv   = $token !== '' ? invite_get($token) : false;
    if (!$inv || !invite_is_active($inv)) {
        flash('error', 'Enlace de invitación no válido o caducado.');
        redirect('?action=login');
    }
    $_SESSION['pending_invite'] = $token;
    if (empty($_SESSION['user_id'])) {
        flash('info', 'Inicia sesión o regístrate para unirte al grupo.');
        redirect('?action=login');
    }
    redirect('?action=invite_accept');
}

if ($action === 'logout') {
    user_logout();
    session_start();
    flash('success', 'Sesión cerrada.');
    redirect('?action=login');
}

// ── EVERYTHING BELOW REQUIRES AUTHENTICATION ───────────────────────────────
require_auth();
$me     = current_user();
$me_id  = (int)$me['id'];

// ── PROFILE ────────────────────────────────────────────────────────────────

if ($action === 'profile') {
    require __DIR__ . '/views/profile.php';
    exit;
}

if ($action === 'profile_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = user_update_name($me_id, post('name'));
    if ($err) flash('warning', $err);
    else      flash('success', 'Nombre actualizado.');
    redirect('?action=profile');
}

if ($action === 'profile_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = user_change_password($me_id, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
    if ($err) flash('error',   $err);
    else      flash('success', 'Contraseña actualizada.');
    redirect('?action=profile');
}

if ($action === 'profile_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['confirm'] ?? '') !== 'on') {
        flash('warning', 'Marca la casilla de confirmación para eliminar tu cuenta.');
        redirect('?action=profile');
    }
    $err = user_delete($me_id, $_POST['password'] ?? '');
    if ($err) { flash('error', $err); redirect('?action=profile'); }
    user_logout();
    session_start();
    flash('success', 'Cuenta eliminada. Todos tus datos han sido borrados.');
    redirect('?action=login');
}

// ── GROUPS ──────────────────────────────────────────────────────────────────

if ($action === 'group_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post('name');
    $cur  = post('base_currency', 'EUR');
    if ($name === '') { $error = 'El nombre es obligatorio.'; goto group_new_view; }
    $new_id = group_create($name, $cur, $me_id);
    activity_log($new_id, $me_id, $me['name'], 'group_create', "Creó el grupo «{$name}»");
    flash('success', "Grupo «{$name}» creado.");
    redirect("?action=group_edit&id=$new_id");
}

if ($action === 'group_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_access($me_id, $id);
    $name = post('name');
    $cur  = post('base_currency', 'EUR');
    if (isset($_POST['_add_participant'])) {
        $pname = post('new_participant');
        if ($pname !== '') {
            participant_create($id, $pname);
            activity_log($id, $me_id, $me['name'], 'participant_create', "Añadió participante «{$pname}»");
            flash('success', "Participante «{$pname}» añadido.");
        } else {
            flash('warning', 'El nombre del participante no puede estar vacío.');
        }
        redirect("?action=group_edit&id=$id");
    }
    if ($name === '') { $error = 'El nombre es obligatorio.'; goto group_edit_view; }
    group_update($id, $name, $cur);
    activity_log($id, $me_id, $me['name'], 'group_update', "Actualizó datos del grupo (nombre/divisa)");
    flash('success', 'Grupo actualizado.');
    redirect("?action=group_edit&id=$id");
}

if ($action === 'group_delete') {
    require_group_owner($me_id, $id);
    $g = group_get($id);
    group_delete($id);
    flash('success', "Grupo «" . ($g['name'] ?? '') . "» eliminado.");
    redirect('?');
}

if ($action === 'group_new') {
    group_new_view:
    $g = ['name' => post('name'), 'base_currency' => post('base_currency', 'EUR')];
    $participants = [];
    $members = [];
    require __DIR__ . '/views/group_form.php';
    exit;
}

if ($action === 'group_edit') {
    group_edit_view:
    require_group_access($me_id, $id);
    $g       = group_get($id);
    if (!$g) { flash('error', 'Grupo no encontrado.'); redirect('?'); }
    $participants = participant_list($id);
    $members      = group_member_list($id);
    $invites      = invite_list($id);
    $base_url     = base_url();
    require __DIR__ . '/views/group_form.php';
    exit;
}

// ── GROUP MEMBERS (invite users) ───────────────────────────────────────────

if ($action === 'member_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_owner($me_id, $gid);
    $email = post('email');
    $r = group_member_add_by_email($gid, $email);
    if (isset($r['error'])) {
        flash('warning', $r['error']);
    } else {
        activity_log($gid, $me_id, $me['name'], 'member_add', "Dio acceso a $email");
        flash('success', 'Usuario añadido al grupo.');
    }
    redirect("?action=group_edit&id=$gid");
}

if ($action === 'member_remove') {
    require_group_owner($me_id, $gid);
    $removed = null;
    foreach (group_member_list($gid) as $m) {
        if ((int)$m['id'] === $id) { $removed = $m; break; }
    }
    group_member_remove($gid, $id);
    if ($removed) {
        activity_log($gid, $me_id, $me['name'], 'member_remove', "Expulsó a {$removed['name']}");
    }
    flash('success', 'Miembro eliminado del grupo.');
    redirect("?action=group_edit&id=$gid");
}

// ── OWNERSHIP TRANSFER ─────────────────────────────────────────────────────

if ($action === 'group_transfer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_owner($me_id, $id);
    $new_owner_id = (int)post('new_owner_id');
    $err = group_transfer_ownership($id, $me_id, $new_owner_id);
    if ($err) {
        flash('error', $err);
    } else {
        $new_name = '';
        foreach (group_member_list($id) as $m) {
            if ((int)$m['id'] === $new_owner_id) { $new_name = $m['name']; break; }
        }
        activity_log($id, $me_id, $me['name'], 'ownership_transfer', "Transfirió la propiedad a $new_name");
        flash('success', "Propiedad transferida a $new_name.");
    }
    redirect("?action=group_edit&id=$id");
}

// ── GROUP INVITES (links) ──────────────────────────────────────────────────

if ($action === 'invite_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_owner($me_id, $gid);
    $ttl_days = (int)post('ttl_days', '7');
    $max_uses = (int)post('max_uses', '0');
    $token    = invite_create($gid, $me_id, $ttl_days, $max_uses);
    activity_log($gid, $me_id, $me['name'], 'invite_create', 'Creó un enlace de invitación');
    flash('success', 'Enlace de invitación creado.');
    redirect("?action=group_edit&id=$gid#invites");
}

if ($action === 'invite_revoke' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_owner($me_id, $gid);
    $token = (string)post('token');
    invite_revoke($token, $gid);
    activity_log($gid, $me_id, $me['name'], 'invite_revoke', 'Revocó un enlace de invitación');
    flash('success', 'Enlace revocado.');
    redirect("?action=group_edit&id=$gid#invites");
}

if ($action === 'invite_accept') {
    $token = (string)($_SESSION['pending_invite'] ?? '');
    if ($token === '') { flash('error', 'No hay invitación pendiente.'); redirect('?'); }
    $r = invite_consume($token, $me_id);
    unset($_SESSION['pending_invite']);
    if (isset($r['error'])) { flash('error', $r['error']); redirect('?'); }
    if (!empty($r['already_member'])) {
        flash('info', 'Ya eras miembro de este grupo.');
    } else {
        activity_log((int)$r['group_id'], $me_id, $me['name'], 'member_join', "Se unió mediante enlace");
        flash('success', '¡Te has unido al grupo!');
    }
    redirect('?g=' . (int)$r['group_id']);
}

// ── ACTIVITY FEED ──────────────────────────────────────────────────────────

if ($action === 'activity') {
    require_group_access($me_id, $gid);
    $group     = group_get($gid);
    $per_page  = 25;
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $total     = activity_count($gid);
    $pages     = max(1, (int)ceil($total / $per_page));
    if ($page > $pages) $page = $pages;
    $entries   = activity_list($gid, $per_page, ($page - 1) * $per_page);
    require __DIR__ . '/views/activity.php';
    exit;
}

// ── STATS ──────────────────────────────────────────────────────────────────

if ($action === 'stats') {
    require_group_access($me_id, $gid);
    $group        = group_get($gid);
    $stats_filter = [
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to'   => trim((string)($_GET['date_to']   ?? '')),
    ];
    $stats_total = stats_totals($gid, $stats_filter);
    $stats_cat   = stats_by_category($gid, $stats_filter);
    $stats_payer = stats_by_payer($gid, $stats_filter);
    $stats_month = stats_by_month($gid, $stats_filter);
    require __DIR__ . '/views/stats.php';
    exit;
}

// ── PARTICIPANTS ────────────────────────────────────────────────────────────

if ($action === 'participant_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_group_access($me_id, $gid);
    $name = post('name');
    if ($name === '') {
        flash('warning', 'El nombre es obligatorio.');
        redirect("?action=group_edit&id=$gid");
    }
    participant_create($gid, $name);
    activity_log($gid, $me_id, $me['name'], 'participant_create', "Añadió participante «{$name}»");
    flash('success', "Participante «{$name}» creado.");
    redirect("?action=group_edit&id=$gid");
}

if ($action === 'participant_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = participant_get($id);
    if (!$p) { flash('error', 'Participante no encontrado.'); redirect('?'); }
    require_group_access($me_id, (int)$p['group_id']);
    $name = post('name');
    if ($name !== '') {
        participant_update($id, $name);
        activity_log((int)$p['group_id'], $me_id, $me['name'], 'participant_update', "Renombró participante «{$p['name']}» → «{$name}»");
        flash('success', 'Participante actualizado.');
    } else {
        flash('warning', 'El nombre no puede estar vacío.');
    }
    redirect("?action=group_edit&id=$gid");
}

if ($action === 'participant_delete') {
    $p = participant_get($id);
    if (!$p) { flash('error', 'Participante no encontrado.'); redirect('?'); }
    require_group_access($me_id, (int)$p['group_id']);
    participant_delete($id);
    activity_log((int)$p['group_id'], $me_id, $me['name'], 'participant_delete', "Eliminó participante «{$p['name']}»");
    flash('success', "Participante «" . ($p['name'] ?? '') . "» eliminado.");
    redirect("?action=group_edit&id=$gid");
}

if ($action === 'participant_edit') {
    require_group_access($me_id, $gid);
    $group       = group_get($gid);
    $participant = participant_get($id);
    require __DIR__ . '/views/participant_form.php';
    exit;
}

// ── TRANSACTIONS ────────────────────────────────────────────────────────────

if ($action === 'tx_new') {
    require_group_access($me_id, $gid);
    $group = group_get($gid);
    if (!$group) { flash('error', 'Grupo no encontrado.'); redirect('?'); }
    if (empty(participant_list($gid))) {
        flash('warning', 'Añade al menos un participante antes de crear transacciones.');
        redirect("?action=group_edit&id=$gid");
    }
    $participants = participant_list($gid);
    $existing_shares = [];
    require __DIR__ . '/views/tx_form.php';
    exit;
}

if ($action === 'tx_edit') {
    require_group_access($me_id, $gid);
    $group           = group_get($gid);
    $tx              = tx_get($id);
    $participants    = participant_list($gid);
    $existing_shares = tx_shares($id);
    require __DIR__ . '/views/tx_form.php';
    exit;
}

if ($action === 'tx_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid   = (int)post('g');
    require_group_access($me_id, $gid);
    $group = group_get($gid);
    [$error, $data] = parse_tx_post($gid, $group['base_currency']);
    if ($error) {
        flash('error', $error);
        $participants    = participant_list($gid);
        $existing_shares = [];
        $tx              = null;
        require __DIR__ . '/views/tx_form.php';
        exit;
    }
    try {
        tx_create($data);
        $amt = number_format($data['amount_cents'] / 100, 2);
        activity_log($gid, $me_id, $me['name'], 'tx_create', "Creó transacción ({$data['type']}, $amt {$data['currency']})");
        flash('success', 'Transacción creada.');
    } catch (Throwable $e) {
        flash('error', 'Error: ' . $e->getMessage());
    }
    redirect("?g=$gid");
}

if ($action === 'tx_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid   = (int)post('g');
    require_group_access($me_id, $gid);
    $group = group_get($gid);
    [$error, $data] = parse_tx_post($gid, $group['base_currency']);
    if ($error) {
        flash('error', $error);
        $participants    = participant_list($gid);
        $tx              = tx_get($id);
        $existing_shares = tx_shares($id);
        require __DIR__ . '/views/tx_form.php';
        exit;
    }
    try {
        tx_update($id, $data);
        $amt = number_format($data['amount_cents'] / 100, 2);
        activity_log($gid, $me_id, $me['name'], 'tx_update', "Editó transacción #$id ($amt {$data['currency']})");
        flash('success', 'Transacción actualizada.');
    } catch (Throwable $e) {
        flash('error', 'Error: ' . $e->getMessage());
    }
    redirect("?g=$gid");
}

if ($action === 'tx_delete') {
    $tx = tx_get($id);
    if (!$tx) { flash('error', 'Transacción no encontrada.'); redirect('?'); }
    require_group_access($me_id, (int)$tx['group_id']);
    $amt = number_format(((int)$tx['amount_cents']) / 100, 2);
    tx_delete($id);
    activity_log((int)$tx['group_id'], $me_id, $me['name'], 'tx_delete', "Eliminó transacción ({$tx['type']}, $amt {$tx['currency']})");
    flash('success', 'Transacción eliminada.');
    redirect("?g=" . $tx['group_id']);
}

// ── CSV EXPORT / IMPORT ────────────────────────────────────────────────────

if ($action === 'tx_export') {
    require_group_access($me_id, $gid);
    $group = group_get($gid);
    $slug  = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $group['name']);
    $fname = sprintf('%s_%s.csv', $slug, date('Ymd'));
    // Replace default text/html content type set by router (CSP etc remain)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Cache-Control: no-store');
    csv_export_stream($gid);
    exit;
}

if ($action === 'tx_import') {
    require_group_access($me_id, $gid);
    $group        = group_get($gid);
    $participants = participant_list($gid);
    $result       = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Adjunta un archivo CSV válido.');
            redirect("?action=tx_import&g=$gid");
        }
        if (($_FILES['csv']['size'] ?? 0) > 2 * 1024 * 1024) { // 2 MB cap
            flash('error', 'El archivo excede 2 MB.');
            redirect("?action=tx_import&g=$gid");
        }
        $parsed = csv_import_parse($_FILES['csv']['tmp_name']);
        if (!empty($parsed['error'])) {
            flash('error', $parsed['error']);
            redirect("?action=tx_import&g=$gid");
        }
        if (empty($participants)) {
            flash('warning', 'Añade al menos un participante antes de importar.');
            redirect("?action=group_edit&id=$gid");
        }
        $result = csv_import_commit($gid, $parsed['rows']);
        flash(
            $result['ok'] > 0 ? 'success' : 'warning',
            "Importadas {$result['ok']} transacciones · " . count($result['errors']) . ' errores.'
        );
    }
    require __DIR__ . '/views/import.php';
    exit;
}

// ── GROUP DETAIL (default when ?g=N) ───────────────────────────────────────

if ($gid > 0) {
    require_group_access($me_id, $gid);
    $group = group_get($gid);
    if (!$group) { flash('error', 'Grupo no encontrado.'); redirect('?'); }
    $participants = participant_list($gid);
    $filters = [
        'sort'      => trim((string)($_GET['sort']      ?? '')),
        'dir'       => trim((string)($_GET['dir']       ?? '')),
        'payer'     => trim((string)($_GET['payer']     ?? '')),
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to'   => trim((string)($_GET['date_to']   ?? '')),
        'category'  => trim((string)($_GET['category']  ?? '')),
        'type'      => trim((string)($_GET['type']      ?? '')),
    ];
    $per_page     = TX_PER_PAGE;
    $page         = max(1, (int)($_GET['page'] ?? 1));
    $total        = tx_count($gid, $filters);
    $pages        = max(1, (int)ceil($total / $per_page));
    if ($page > $pages) $page = $pages;
    $transactions = tx_list($gid, $filters, $per_page, ($page - 1) * $per_page);
    $categories   = categories_in_group($gid);
    $settlement   = compute_settlement($gid);
    require __DIR__ . '/views/group_detail.php';
    exit;
}

// ── HOME (only my groups) ──────────────────────────────────────────────────

$groups = group_list($me_id);
require __DIR__ . '/views/groups.php';
exit;

// ── TX POST PARSER ─────────────────────────────────────────────────────────

function parse_tx_post(int $gid, string $base_currency): array {
    $type   = post('type');
    $payer  = (int)post('payer_participant_id');
    $amt_s  = post('amount');
    $cur    = strtoupper(post('currency', $base_currency));
    $cat    = post('category');
    $note   = post('note');
    $occ    = post('occurred_at', date('Y-m-d H:i:s'));
    $occ    = str_replace('T', ' ', $occ);

    if ($amt_s === '') return ['Importe requerido.', null];
    $cents = dollars_to_cents($amt_s);
    if ($cents <= 0) return ['El importe debe ser positivo.', null];
    if ($payer <= 0) return ['Selecciona un pagador.', null];

    $data = [
        'group_id'              => $gid,
        'type'                  => $type,
        'payer_participant_id'  => $payer,
        'amount_cents'          => $cents,
        'currency'              => $cur,
        'category'              => $cat,
        'note'                  => $note,
        'occurred_at'           => $occ,
    ];

    if ($type === 'expense_equal') {
        $ids = array_map('intval', (array)($_POST['participant_ids'] ?? []));
        if (empty($ids)) return ['Selecciona al menos un participante.', null];
        $data['participant_ids'] = $ids;
    } elseif ($type === 'expense_shares') {
        $raw = $_POST['shares'] ?? [];
        $shares = [];
        $total  = 0;
        foreach ($raw as $pid => $val) {
            $sc = dollars_to_cents((string)$val);
            if ($sc > 0) { $shares[] = ['participant_id' => (int)$pid, 'share_cents' => $sc]; $total += $sc; }
        }
        if (empty($shares)) return ['Define al menos un reparto.', null];
        if (abs($total - $cents) > 1) {
            $exp = number_format($cents/100, 2);
            $got = number_format($total/100, 2);
            return ["La suma de repartos ($got) no coincide con el importe ($exp).", null];
        }
        $data['shares'] = $shares;
    } elseif ($type === 'expense_full' || $type === 'settlement') {
        $ben = (int)post('beneficiary_id');
        if ($ben <= 0) return ['Selecciona un beneficiario.', null];
        if ($ben === $payer) return ['El beneficiario debe ser distinto del pagador.', null];
        $data['beneficiary_id'] = $ben;
    } elseif ($type === 'gift') {
        // no extra fields
    } else {
        return ['Tipo de transacción inválido.', null];
    }

    return [null, $data];
}
