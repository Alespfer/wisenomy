<?php
$editing = !empty($participant);
$title   = $editing ? 'Editar participante' : 'Nuevo participante';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><?= $title ?></h2>
        <p class="text-muted small mb-0">
            Grupo: <strong><?= htmlspecialchars($group['name']) ?></strong>
        </p>
    </div>
    <a href="?action=group_edit&id=<?= $group['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="?action=<?= $editing ? 'participant_update' : 'participant_create' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="g" value="<?= $group['id'] ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $participant['id'] ?>"><?php endif ?>

            <label class="form-label">Nombre del participante</label>
            <input type="text" name="name" class="form-control" required maxlength="80"
                   placeholder="Ej. Alice"
                   value="<?= htmlspecialchars($participant['name'] ?? '') ?>" autofocus>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-brand">
                    <i class="bi <?= $editing ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                    <?= $editing ? 'Guardar' : 'Crear' ?>
                </button>
                <a href="?action=group_edit&id=<?= $group['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
