<?php
$title = 'Importar CSV — ' . $group['name'];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Importar transacciones</h2>
        <p class="text-muted small mb-0">
            Grupo: <strong><?= htmlspecialchars($group['name']) ?></strong>
            <span class="currency-pill ms-1"><?= htmlspecialchars($group['base_currency']) ?></span>
        </p>
    </div>
    <a href="?g=<?= $group['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-upload"></i> Subir archivo CSV</div>
    <div class="card-body">
        <form method="post" action="?action=tx_import&g=<?= $group['id'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
                <div class="form-text small">Tamaño máximo: 2 MB. Codificación UTF-8 recomendada.</div>
            </div>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-cloud-upload"></i> Importar
            </button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle"></i> Formato esperado</div>
    <div class="card-body">
        <p class="small text-muted mb-2">Cabecera obligatoria (orden libre, mayúsculas/minúsculas indiferentes):</p>
        <pre class="bg-light p-2 rounded small mb-3">date,type,payer,amount,currency,beneficiary,shares,category,note</pre>
        <ul class="small mb-2">
            <li><code>date</code>: <code>YYYY-MM-DD</code> o <code>YYYY-MM-DD HH:MM:SS</code></li>
            <li><code>type</code>: <code>expense_equal</code>, <code>expense_shares</code>, <code>expense_full</code>, <code>gift</code> o <code>settlement</code></li>
            <li><code>payer</code>: nombre exacto de un participante del grupo</li>
            <li><code>amount</code>: decimal con punto o coma</li>
            <li><code>currency</code>: código ISO de 3 letras (EUR, USD, SEK…)</li>
            <li><code>beneficiary</code>: requerido solo para <code>expense_full</code> y <code>settlement</code></li>
            <li><code>shares</code>: requerido solo para <code>expense_shares</code>. Formato <code>Nombre:importe|Nombre:importe</code>. La suma debe coincidir con <code>amount</code>.</li>
            <li><strong>expense_equal</strong>: se reparte automáticamente entre <strong>todos</strong> los participantes del grupo.</li>
        </ul>
        <p class="small text-muted mb-1">Ejemplo:</p>
        <pre class="bg-light p-2 rounded small mb-0">date,type,payer,amount,currency,beneficiary,shares,category,note
2026-05-15 20:00:00,expense_equal,Alice,90,USD,,,Comida,Cena
2026-05-16 09:00:00,expense_full,Bob,30,EUR,Alice,,Transporte,Taxi aeropuerto
2026-05-17 14:00:00,expense_shares,Alice,100,EUR,,Alice:20|Bob:30|Carlos:50,Ocio,Cena reparto
2026-05-17 18:00:00,gift,Carlos,5,EUR,,,Otro,Helados
2026-05-20 12:00:00,settlement,Carlos,10,EUR,Alice,,,Pago parcial</pre>
    </div>
</div>

<?php if ($result !== null): ?>
<div class="card">
    <div class="card-header">
        <i class="bi bi-clipboard-check"></i> Resultado de la importación
    </div>
    <div class="card-body">
        <p class="mb-2">
            <span class="badge bg-success me-2">
                <i class="bi bi-check-circle"></i> <?= $result['ok'] ?> importadas
            </span>
            <?php if (!empty($result['errors'])): ?>
            <span class="badge bg-danger">
                <i class="bi bi-x-circle"></i> <?= count($result['errors']) ?> con errores
            </span>
            <?php endif ?>
        </p>

        <?php if (!empty($result['errors'])): ?>
        <h6 class="mt-3 mb-2 small text-uppercase text-muted">Errores</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th style="width:80px">Línea</th><th>Mensaje</th></tr></thead>
            <tbody>
            <?php foreach ($result['errors'] as $e): ?>
            <tr>
                <td><?= (int)$e['line'] ?></td>
                <td class="text-danger small"><?= htmlspecialchars($e['msg']) ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        </div>
        <?php endif ?>

        <?php if ($result['ok'] > 0): ?>
        <div class="mt-3">
            <a href="?g=<?= $group['id'] ?>" class="btn btn-brand">
                <i class="bi bi-arrow-right"></i> Ver transacciones
            </a>
        </div>
        <?php endif ?>
    </div>
</div>
<?php endif ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
