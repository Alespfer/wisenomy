<?php
$title = $group['name'];
ob_start();

function fmt(int $cents, string $currency = ''): string {
    $s = number_format(abs($cents) / 100, 2);
    return ($cents < 0 ? '-' : '') . $s . ($currency ? " $currency" : '');
}

// Cycle sort state: not-set → asc → desc → not-set
function sort_url(string $col, array $f, int $gid): string {
    $params = ['g' => $gid];
    foreach (['payer','date_from','date_to','category','type'] as $k) {
        if (!empty($f[$k])) $params[$k] = $f[$k];
    }
    // page is intentionally NOT preserved → resets to 1 when sorting
    $cur_sort = $f['sort'] ?? '';
    $cur_dir  = $f['dir']  ?? '';
    if ($cur_sort === $col) {
        if ($cur_dir === 'asc') { $params['sort'] = $col; $params['dir'] = 'desc'; }
        // else: dir was desc (or other) → cycle back to default (no sort)
    } else {
        $params['sort'] = $col;
        $params['dir']  = 'asc';
    }
    return '?' . http_build_query($params);
}

// Builds a paginator URL that preserves all current filters + sort.
function tx_pg_url(int $gid, array $f, int $page): string {
    $params = ['g' => $gid];
    foreach (['payer','date_from','date_to','category','type','sort','dir'] as $k) {
        if (!empty($f[$k])) $params[$k] = $f[$k];
    }
    if ($page > 1) $params['page'] = $page;
    return '?' . http_build_query($params);
}

function sort_arrow(string $col, array $f): string {
    if (($f['sort'] ?? '') !== $col) return '↕';
    return ($f['dir'] ?? '') === 'asc' ? '↑' : '↓';
}

function sort_class(string $col, array $f): string {
    return (($f['sort'] ?? '') === $col) ? 'sort-active' : '';
}

// EUR conversion rate (only when base != EUR)
$eur_rate  = null;
$eur_stale = false;
if ($group['base_currency'] !== 'EUR') {
    try {
        $fx        = fx_get($group['base_currency'], 'EUR');
        $eur_rate  = $fx['rate'];
        $eur_stale = $fx['stale'];
    } catch (Throwable $e) {
        $eur_rate = null;
    }
}

$type_labels = [
    'expense_equal'  => 'Igual',
    'expense_shares' => 'Reparto',
    'expense_full'   => 'Completo',
    'gift'           => 'Regalo',
    'settlement'     => 'Liquidación',
];
$type_icons = [
    'expense_equal'  => 'bi-people',
    'expense_shares' => 'bi-pie-chart',
    'expense_full'   => 'bi-person-check',
    'gift'           => 'bi-gift',
    'settlement'     => 'bi-arrow-left-right',
];

$has_filters = !empty($filters['payer']) || !empty($filters['date_from']) || !empty($filters['date_to'])
            || !empty($filters['category']) || !empty($filters['type']);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">
            <?= htmlspecialchars($group['name']) ?>
            <span class="currency-pill ms-2"><?= htmlspecialchars($group['base_currency']) ?></span>
        </h2>
        <p class="text-muted small mb-0">
            <i class="bi bi-people"></i> <?= count($participants) ?> participantes ·
            <i class="bi bi-receipt"></i> <?= (int)$total ?> transacciones
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?action=stats&g=<?= $group['id'] ?>" class="btn btn-outline-secondary" title="Estadísticas">
            <i class="bi bi-bar-chart-line"></i> <span class="d-none d-md-inline">Estadísticas</span>
        </a>
        <a href="?action=activity&g=<?= $group['id'] ?>" class="btn btn-outline-secondary" title="Actividad">
            <i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Actividad</span>
        </a>
        <a href="?action=tx_import&g=<?= $group['id'] ?>" class="btn btn-outline-secondary" title="Importar CSV">
            <i class="bi bi-cloud-upload"></i> <span class="d-none d-md-inline">Importar</span>
        </a>
        <a href="?action=tx_export&g=<?= $group['id'] ?>" class="btn btn-outline-secondary" title="Exportar CSV">
            <i class="bi bi-cloud-download"></i> <span class="d-none d-md-inline">Exportar</span>
        </a>
        <a href="?action=group_edit&id=<?= $group['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-gear"></i> <span class="d-none d-md-inline">Configurar</span>
        </a>
        <a href="?action=tx_new&g=<?= $group['id'] ?>" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Nueva
        </a>
    </div>
</div>

<!-- BALANCE -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart"></i> Balance actual (<?= $group['base_currency'] ?>)</span>
    </div>
    <div class="card-body">
        <?php if (empty($participants)): ?>
            <div class="empty-state py-3">
                <i class="bi bi-person-plus"></i>
                <p class="mb-2">Aún no hay participantes en este grupo.</p>
                <a href="?action=group_edit&id=<?= $group['id'] ?>" class="btn btn-sm btn-brand">
                    Añadir participantes
                </a>
            </div>
        <?php else: ?>
        <div class="row g-3 mb-3">
            <?php foreach ($settlement['balances'] as $b):
                $cls  = $b['cents'] > 0 ? 'balance-positive' : ($b['cents'] < 0 ? 'balance-negative' : 'balance-zero');
                $icon = $b['cents'] > 0 ? 'bi-arrow-up-circle-fill' : ($b['cents'] < 0 ? 'bi-arrow-down-circle-fill' : 'bi-check-circle-fill');
            ?>
            <div class="col-sm-6 col-md-4">
                <div class="card h-100" style="background:#f8fafc">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($b['name']) ?></div>
                                <div class="fs-5 <?= $cls ?>"><?= fmt($b['cents']) ?> <?= $group['base_currency'] ?></div>
                            </div>
                            <i class="bi <?= $icon ?> <?= $cls ?>" style="font-size:1.8rem"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>

        <?php if (!empty($settlement['payments'])): ?>
        <h6 class="mt-4 mb-3 text-muted text-uppercase small">
            <i class="bi bi-arrow-right-circle"></i> Pagos mínimos para saldar
        </h6>
        <div class="row g-2">
            <?php foreach ($settlement['payments'] as $pay): ?>
            <div class="col-md-6">
                <div class="payment-card">
                    <i class="bi bi-person-fill text-danger"></i>
                    <strong><?= htmlspecialchars($pay['from']) ?></strong>
                    <i class="bi bi-arrow-right text-muted"></i>
                    <strong><?= htmlspecialchars($pay['to']) ?></strong>
                    <span class="ms-auto fw-bold text-success"><?= fmt($pay['cents']) ?> <?= $group['base_currency'] ?></span>
                </div>
            </div>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div class="alert alert-success d-flex align-items-center mb-0">
            <i class="bi bi-check-circle-fill me-2"></i>
            Todos los saldos están a cero. Sin pagos pendientes.
        </div>
        <?php endif ?>
        <?php endif ?>
    </div>
</div>

<!-- TRANSACCIONES -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> Transacciones</span>
        <a href="?action=tx_new&g=<?= $group['id'] ?>" class="btn btn-sm btn-brand">
            <i class="bi bi-plus-lg"></i> Nueva
        </a>
    </div>

    <!-- Barra de filtros -->
    <div class="filter-bar">
        <form method="get" action="" class="row g-2 align-items-end">
            <input type="hidden" name="g" value="<?= $group['id'] ?>">
            <?php if (!empty($filters['sort'])): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
                <input type="hidden" name="dir"  value="<?= htmlspecialchars($filters['dir']) ?>">
            <?php endif ?>

            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">Pagador</label>
                <input type="text" name="payer" class="form-control form-control-sm"
                       placeholder="Buscar..."
                       value="<?= htmlspecialchars($filters['payer'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">Categoría</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">— todas —</option>
                    <option value="__none__" <?= ($filters['category'] ?? '') === '__none__' ? 'selected' : '' ?>>(sin categoría)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">Tipo</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">— todos —</option>
                    <?php foreach ($type_labels as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($filters['type'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">Desde</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">Hasta</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-brand flex-fill">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <?php if ($has_filters || !empty($filters['sort'])): ?>
                <a href="?g=<?= $group['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif ?>
            </div>
        </form>
        <?php if ($has_filters): ?>
        <div class="small text-muted mt-2">
            <i class="bi bi-funnel-fill"></i> <?= (int)$total ?> resultado<?= $total === 1 ? '' : 's' ?> con filtros activos
        </div>
        <?php endif ?>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="card-body empty-state">
            <i class="bi bi-receipt"></i>
            <h6><?= $has_filters ? 'Sin resultados' : 'Sin transacciones' ?></h6>
            <p class="mb-3">
                <?= $has_filters
                    ? 'Ningún registro coincide con los filtros actuales.'
                    : 'Registra el primer gasto del grupo.' ?>
            </p>
            <?php if ($has_filters): ?>
                <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Limpiar filtros
                </a>
            <?php else: ?>
                <a href="?action=tx_new&g=<?= $group['id'] ?>" class="btn btn-brand">
                    <i class="bi bi-plus-lg"></i> Nueva transacción
                </a>
            <?php endif ?>
        </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead>
            <tr>
                <th>
                    <a class="sort-link <?= sort_class('occurred_at', $filters) ?>"
                       href="<?= sort_url('occurred_at', $filters, $group['id']) ?>">
                        Fecha <span class="sort-arrow"><?= sort_arrow('occurred_at', $filters) ?></span>
                    </a>
                </th>
                <th>
                    <a class="sort-link <?= sort_class('type', $filters) ?>"
                       href="<?= sort_url('type', $filters, $group['id']) ?>">
                        Tipo <span class="sort-arrow"><?= sort_arrow('type', $filters) ?></span>
                    </a>
                </th>
                <th>
                    <a class="sort-link <?= sort_class('payer', $filters) ?>"
                       href="<?= sort_url('payer', $filters, $group['id']) ?>">
                        Pagador <span class="sort-arrow"><?= sort_arrow('payer', $filters) ?></span>
                    </a>
                </th>
                <th>
                    <a class="sort-link <?= sort_class('amount_base_cents', $filters) ?>"
                       href="<?= sort_url('amount_base_cents', $filters, $group['id']) ?>">
                        Importe <span class="sort-arrow"><?= sort_arrow('amount_base_cents', $filters) ?></span>
                    </a>
                </th>
                <th class="d-none d-md-table-cell">
                    <a class="sort-link <?= sort_class('category', $filters) ?>"
                       href="<?= sort_url('category', $filters, $group['id']) ?>">
                        Categoría <span class="sort-arrow"><?= sort_arrow('category', $filters) ?></span>
                    </a>
                </th>
                <th class="d-none d-lg-table-cell">Nota</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $tx): ?>
        <tr>
            <td class="text-nowrap small text-muted"><?= fmt_date($tx['occurred_at']) ?></td>
            <td>
                <span class="tx-type-badge tx-type-<?= $tx['type'] ?>">
                    <i class="bi <?= $type_icons[$tx['type']] ?? 'bi-circle' ?>"></i>
                    <?= $type_labels[$tx['type']] ?? $tx['type'] ?>
                </span>
            </td>
            <td><i class="bi bi-person-circle text-muted"></i> <?= htmlspecialchars($tx['payer_name']) ?></td>
            <td class="text-nowrap">
                <strong><?= fmt($tx['amount_cents']) ?></strong>
                <span class="text-muted small"><?= htmlspecialchars($tx['currency']) ?></span>
                <?php if ($tx['currency'] !== $group['base_currency']): ?>
                    <div class="small text-muted">
                        ≈ <?= fmt($tx['amount_base_cents']) ?> <?= $group['base_currency'] ?>
                        <?php if ($tx['fx_stale']): ?>
                            <span class="fx-stale-badge" title="Tasa desactualizada (sin conexión)">stale</span>
                        <?php endif ?>
                    </div>
                <?php endif ?>
                <?php if ($group['base_currency'] !== 'EUR'): ?>
                    <?php if ($eur_rate !== null): ?>
                        <div class="eur-subtitle">
                            ≈ <?= number_format(abs($tx['amount_base_cents']) * $eur_rate / 100, 2) ?> EUR
                            <?php if ($eur_stale): ?>
                                <span class="fx-stale-badge" title="Tasa EUR desactualizada">stale</span>
                            <?php endif ?>
                        </div>
                    <?php else: ?>
                        <div class="eur-subtitle">≈ EUR n/d</div>
                    <?php endif ?>
                <?php endif ?>
            </td>
            <td class="d-none d-md-table-cell small"><?= htmlspecialchars($tx['category']) ?: '<span class="text-muted">—</span>' ?></td>
            <td class="d-none d-lg-table-cell small text-muted text-truncate" style="max-width:180px">
                <?= htmlspecialchars($tx['note']) ?>
            </td>
            <td class="text-nowrap text-end">
                <a href="?action=tx_edit&id=<?= $tx['id'] ?>&g=<?= $group['id'] ?>"
                   class="btn btn-sm btn-outline-secondary" title="Editar">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="?action=tx_delete&id=<?= $tx['id'] ?>&g=<?= $group['id'] ?>"
                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                   onclick="return confirm('¿Eliminar esta transacción?')">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1):
        $first = ($page - 1) * $per_page + 1;
        $last  = min($total, $page * $per_page);
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
    ?>
    <nav class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 border-top">
        <span class="small text-muted">
            Mostrando <?= $first ?>–<?= $last ?> de <?= $total ?>
        </span>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= tx_pg_url($group['id'], $filters, max(1, $page - 1)) ?>">&laquo;</a>
            </li>
            <?php if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= tx_pg_url($group['id'], $filters, 1) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
            <?php endif ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= tx_pg_url($group['id'], $filters, $p) ?>"><?= $p ?></a>
                </li>
            <?php endfor ?>
            <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
                <li class="page-item"><a class="page-link" href="<?= tx_pg_url($group['id'], $filters, $pages) ?>"><?= $pages ?></a></li>
            <?php endif ?>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= tx_pg_url($group['id'], $filters, min($pages, $page + 1)) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif ?>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
