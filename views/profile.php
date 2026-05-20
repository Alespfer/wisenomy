<?php
$title = 'Mi perfil — Wisenomy';
$hide_nav_breadcrumb = true;
$del_summary = user_deletion_summary($me_id);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Mi perfil</h2>
        <p class="text-muted small mb-0">Gestiona tus datos personales y credenciales</p>
    </div>
    <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-person-badge"></i> Datos personales</div>
    <div class="card-body">
        <form method="post" action="?action=profile_update">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required maxlength="80"
                           value="<?= htmlspecialchars($me['name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($me['email']) ?>" disabled>
                    <div class="form-text small">El email no se puede cambiar.</div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> Guardar nombre
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shield-lock"></i> Cambiar contraseña</div>
    <div class="card-body">
        <form method="post" action="?action=profile_password" id="pwform">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Contraseña actual</label>
                <input type="password" name="current_password" class="form-control" required
                       autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" id="pw" name="new_password" class="form-control" required
                       autocomplete="new-password" placeholder="••••••••">
                <ul class="list-unstyled small mt-2 mb-0" id="pw-checks">
                    <li data-check="len"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                    <li data-check="low"><i class="bi bi-circle"></i> Una letra minúscula (a-z)</li>
                    <li data-check="up"><i class="bi bi-circle"></i> Una letra mayúscula (A-Z)</li>
                    <li data-check="num"><i class="bi bi-circle"></i> Un número (0-9)</li>
                    <li data-check="sp"><i class="bi bi-circle"></i> Un carácter especial (!@#$%…)</li>
                </ul>
            </div>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-key"></i> Cambiar contraseña
            </button>
        </form>
    </div>
</div>

<div class="card border-danger">
    <div class="card-header text-bg-danger"><i class="bi bi-exclamation-triangle"></i> Zona de peligro · Eliminar cuenta</div>
    <div class="card-body">
        <p class="mb-2">
            Al eliminar tu cuenta se borrarán de forma <strong>irreversible</strong>:
        </p>
        <ul class="mb-3">
            <li><strong><?= (int)$del_summary['owned_groups'] ?></strong> grupo(s) que creaste,
                con sus <strong><?= (int)$del_summary['owned_txs'] ?></strong> transacciones, participantes y miembros asociados.</li>
            <li>Tu membresía en <strong><?= (int)$del_summary['shared_groups'] ?></strong> grupo(s) de otros usuarios
                (esos grupos no se eliminan, pero perderás el acceso).</li>
            <li>Tus credenciales de acceso (email, contraseña) y cualquier enlace de recuperación pendiente.</li>
        </ul>

        <?php if ((int)$del_summary['owned_groups'] > 0): ?>
        <div class="alert alert-warning small">
            <i class="bi bi-info-circle"></i>
            Los miembros de los grupos que has creado también perderán el acceso a esos grupos.
            Si quieres preservarlos, traspasa la propiedad a otro miembro antes de eliminar tu cuenta
            desde la configuración del grupo.
        </div>
        <?php endif ?>

        <form method="post" action="?action=profile_delete" id="delform"
              onsubmit="return confirm('¿Confirmas la eliminación definitiva de tu cuenta? Esta acción no se puede deshacer.');">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label text-danger">Contraseña actual</label>
                <input type="password" name="password" class="form-control" required
                       autocomplete="current-password" placeholder="••••••••">
                <div class="form-text small">Necesitamos confirmar que eres tú.</div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" id="delconfirm" required>
                <label class="form-check-label small" for="delconfirm">
                    Entiendo que esta acción es <strong>permanente e irreversible</strong> y que perderé todos mis datos.
                </label>
            </div>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash3"></i> Eliminar mi cuenta definitivamente
            </button>
        </form>
    </div>
</div>

<style>
#pw-checks li { color: #94a3b8; transition: color 0.15s; }
#pw-checks li.ok { color: #059669; }
#pw-checks li.ok .bi::before { content: "\f26b"; }
</style>
<script>
(function () {
    const pw = document.getElementById('pw');
    if (!pw) return;
    const tests = {
        len: v => v.length >= 8,
        low: v => /[a-z]/.test(v),
        up:  v => /[A-Z]/.test(v),
        num: v => /[0-9]/.test(v),
        sp:  v => /[^A-Za-z0-9]/.test(v),
    };
    pw.addEventListener('input', () => {
        const v = pw.value;
        Object.entries(tests).forEach(([k, fn]) => {
            document.querySelector(`#pw-checks li[data-check="${k}"]`).classList.toggle('ok', fn(v));
        });
    });
    document.getElementById('pwform').addEventListener('submit', (e) => {
        if (!Object.values(tests).every(fn => fn(pw.value))) {
            e.preventDefault();
            pw.focus();
            pw.classList.add('is-invalid');
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
