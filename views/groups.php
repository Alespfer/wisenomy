<?php
$title = 'Wisenomy — Grupos';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Tus grupos</h2>
        <p class="text-muted small mb-0">Gestión sabia de gastos compartidos</p>
    </div>
    <a href="?action=group_new" class="btn btn-brand">
        <i class="bi bi-plus-lg"></i> Nuevo grupo
    </a>
</div>

<?php if (empty($groups)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <i class="bi bi-people"></i>
            <h5>Aún no hay grupos</h5>
            <p class="mb-3">Crea tu primer grupo para empezar a registrar gastos compartidos.</p>
            <a href="?action=group_new" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Crear primer grupo
            </a>
        </div>
    </div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($groups as $g): ?>
    <div class="col-md-6">
        <div class="card group-card h-100" onclick="location.href='?g=<?= $g['id'] ?>'">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0">
                        <a href="?g=<?= $g['id'] ?>" class="text-decoration-none text-dark stretched-link">
                            <?= htmlspecialchars($g['name']) ?>
                        </a>
                    </h5>
                    <span class="currency-pill"><?= htmlspecialchars($g['base_currency']) ?></span>
                </div>
                <p class="card-text text-muted small mb-3">
                    <i class="bi bi-calendar3"></i>
                    Creado el <?= htmlspecialchars(substr($g['created_at'], 0, 10)) ?>
                </p>
                <div class="d-flex gap-2 position-relative" style="z-index:2">
                    <a href="?action=group_edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="?action=group_delete&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger"
                       onclick="event.stopPropagation(); return confirm('¿Eliminar grupo «<?= htmlspecialchars(addslashes($g['name'])) ?>» y todo su contenido?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
