<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? 'Wisenomy') ?></title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root {
    --brand-grad: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
}
body {
    background: #f8fafc;
    min-height: 100vh;
}
.navbar-brand-grad {
    background: var(--brand-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    letter-spacing: -0.5px;
}
.navbar {
    background: #fff !important;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.card {
    border: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    border-radius: 12px;
}
.card-header {
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    border-radius: 12px 12px 0 0 !important;
    font-weight: 600;
}
.btn-brand {
    background: var(--brand-grad);
    color: #fff;
    border: none;
    font-weight: 500;
    transition: transform 0.1s, box-shadow 0.2s;
}
.btn-brand:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}
.balance-positive { color: #059669; font-weight: 600; }
.balance-negative { color: #dc2626; font-weight: 600; }
.balance-zero     { color: #6b7280; font-weight: 600; }
.tx-type-badge {
    font-size: 0.75rem;
    padding: 0.3em 0.6em;
    border-radius: 6px;
    font-weight: 500;
}
.tx-type-expense_equal  { background: #dbeafe; color: #1e40af; }
.tx-type-expense_shares { background: #ede9fe; color: #6b21a8; }
.tx-type-expense_full   { background: #fef3c7; color: #92400e; }
.tx-type-gift           { background: #fce7f3; color: #9d174d; }
.tx-type-settlement     { background: #d1fae5; color: #065f46; }
.payment-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.fx-stale-badge {
    background: #fef3c7;
    color: #92400e;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 4px;
    font-weight: 600;
}
.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: #6b7280;
}
.empty-state .bi {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 12px;
    display: block;
}
.group-card {
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.2s;
}
.group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.currency-pill {
    display: inline-block;
    background: #f1f5f9;
    color: #475569;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.toast-container {
    z-index: 1100;
}
.form-label { font-weight: 500; font-size: 0.9rem; color: #374151; }
.table thead th {
    background: #f8fafc;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb;
}
.share-input-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}
.share-input-row:last-child { border-bottom: none; }
.share-input-row label {
    flex: 1;
    margin: 0;
    font-weight: 500;
}
.share-input-row input { width: 140px; }
.sort-link {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
}
.sort-link:hover { color: #6366f1; }
.sort-arrow {
    font-size: 0.85em;
    color: #cbd5e1;
    transition: color 0.15s;
}
.sort-active .sort-arrow,
.sort-active {
    color: #6366f1 !important;
    font-weight: 700;
}
.filter-bar {
    background: #fff;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.eur-subtitle {
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 2px;
}
.participant-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 8px 12px;
    margin: 4px 4px 4px 0;
}
</style>
</head>
<body>

<?php $_nav_user = function_exists('current_user') ? current_user() : null; ?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-grad fs-4" href="?">
            <i class="bi bi-cash-coin"></i> Wisenomy
        </a>
        <?php if (!empty($group) && empty($hide_nav_breadcrumb)): ?>
            <span class="text-muted mx-2">/</span>
            <a class="text-decoration-none text-dark fw-semibold" href="?g=<?= $group['id'] ?>">
                <?= htmlspecialchars($group['name']) ?>
            </a>
        <?php endif ?>

        <?php if ($_nav_user): ?>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="?action=profile" class="text-decoration-none small text-muted d-none d-sm-inline" title="Mi perfil">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_nav_user['name']) ?>
            </a>
            <a href="?action=profile" class="btn btn-sm btn-outline-secondary" title="Mi perfil">
                <i class="bi bi-gear"></i>
                <span class="d-none d-md-inline">Perfil</span>
            </a>
            <a href="?action=logout" class="btn btn-sm btn-outline-secondary" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-md-inline">Salir</span>
            </a>
        </div>
        <?php endif ?>
    </div>
</nav>

<main class="container py-4" style="max-width: 960px;">
<?= $content ?>
</main>

<footer class="container py-4 text-center small text-muted" style="max-width: 960px;">
    <hr class="mb-3" style="opacity:0.3">
    <p class="mb-1">
        © <?= date('Y') ?> Wisenomy ·
        <a href="?action=legal"   class="text-muted">Aviso legal</a> ·
        <a href="?action=privacy" class="text-muted">Privacidad</a> ·
        <a href="?action=terms"   class="text-muted">Términos</a>
    </p>
    <p class="mb-0" style="font-size:0.75rem">Gestión sabia de gastos compartidos · Del griego <em>nómos</em>, la norma que reparte.</p>
</footer>

<!-- Toast container for flash messages -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
<?php
$flashes = $_SESSION['flash'] ?? [];
$_SESSION['flash'] = [];
foreach ($flashes as $i => $f):
    $bg = match($f['type']) {
        'success' => 'text-bg-success',
        'error'   => 'text-bg-danger',
        'warning' => 'text-bg-warning',
        default   => 'text-bg-primary',
    };
    $icon = match($f['type']) {
        'success' => 'bi-check-circle-fill',
        'error'   => 'bi-x-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        default   => 'bi-info-circle-fill',
    };
?>
<div class="toast align-items-center <?= $bg ?> border-0 mb-2" role="alert" data-bs-delay="4000">
    <div class="d-flex">
        <div class="toast-body">
            <i class="bi <?= $icon ?> me-2"></i> <?= htmlspecialchars($f['msg']) ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>
<?php endforeach ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());
</script>
</body>
</html>
