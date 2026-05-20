<?php
$title = 'Estadísticas — ' . $group['name'];
ob_start();

function fmt_cents(int $cents): string {
    return number_format($cents / 100, 2);
}

$cur            = $group['base_currency'];
$total_expense  = (int)$stats_total['expense_cents'];

function bar_row(string $label, int $cents, int $max, string $cur, int $count = 0): string {
    $pct = $max > 0 ? round($cents * 100 / $max) : 0;
    $label_h = htmlspecialchars($label);
    $cents_s = number_format($cents / 100, 2);
    $n_str   = $count > 0 ? " <span class=\"text-muted small\">($count tx)</span>" : '';
    return <<<HTML
    <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1">
            <span><strong>$label_h</strong>$n_str</span>
            <span class="text-muted">$cents_s $cur</span>
        </div>
        <div class="progress" style="height:8px">
            <div class="progress-bar" role="progressbar" style="width:{$pct}%;background:var(--brand-grad)"></div>
        </div>
    </div>
HTML;
}
?>
<?php
$df = htmlspecialchars($stats_filter['date_from'] ?? '');
$dt = htmlspecialchars($stats_filter['date_to']   ?? '');
$has_filter = $df !== '' || $dt !== '';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-bar-chart-line"></i> Estadísticas</h2>
        <p class="text-muted small mb-0">
            Resumen de <strong><?= htmlspecialchars($group['name']) ?></strong>
            (importes en <?= htmlspecialchars($cur) ?>)
            <?php if ($has_filter): ?>
                · <span class="text-primary"><i class="bi bi-funnel-fill"></i> filtro activo</span>
            <?php endif ?>
        </p>
    </div>
    <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al grupo
    </a>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="get" action="" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="stats">
            <input type="hidden" name="g" value="<?= $group['id'] ?>">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Desde</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $df ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Hasta</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $dt ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-brand flex-fill">
                    <i class="bi bi-funnel"></i> Aplicar filtro
                </button>
                <?php if ($has_filter): ?>
                <a href="?action=stats&g=<?= $group['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Transacciones</div>
            <div class="fs-4 fw-bold"><?= (int)$stats_total['tx_count'] ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Gastos totales</div>
            <div class="fs-4 fw-bold text-primary"><?= fmt_cents($total_expense) ?> <?= $cur ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Regalos</div>
            <div class="fs-5 fw-bold text-info"><?= fmt_cents((int)$stats_total['gift_cents']) ?> <?= $cur ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Liquidaciones</div>
            <div class="fs-5 fw-bold text-success"><?= fmt_cents((int)$stats_total['settlement_cents']) ?> <?= $cur ?></div>
        </div></div>
    </div>
</div>

<?php if ($total_expense === 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    Aún no hay gastos en este grupo. Registra alguno para ver las estadísticas detalladas.
</div>
<?php else: ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-tags"></i> Gasto por categoría</div>
            <div class="card-body">
                <?php
                $max_cat = $stats_cat ? max(array_map(fn($r) => (int)$r['total_cents'], $stats_cat)) : 0;
                foreach ($stats_cat as $row) {
                    echo bar_row((string)$row['category'], (int)$row['total_cents'], $max_cat, $cur, (int)$row['n']);
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-circle"></i> Gasto pagado por participante</div>
            <div class="card-body">
                <?php
                $max_p = $stats_payer ? max(array_map(fn($r) => (int)$r['total_cents'], $stats_payer)) : 0;
                foreach ($stats_payer as $row) {
                    echo bar_row((string)$row['payer'], (int)$row['total_cents'], $max_p, $cur, (int)$row['n']);
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar3"></i> Gasto por mes</div>
            <div class="card-body">
                <?php
                $max_m = $stats_month ? max(array_map(fn($r) => (int)$r['total_cents'], $stats_month)) : 0;
                foreach ($stats_month as $row) {
                    echo bar_row((string)$row['month'], (int)$row['total_cents'], $max_m, $cur, (int)$row['n']);
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php endif ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
