<?php
$title = 'Nueva contraseña — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="navbar-brand-grad" style="font-size:2rem">
                <i class="bi bi-shield-lock"></i> Wisenomy
            </h1>
            <p class="text-muted">Define una nueva contraseña</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif ?>

        <?php if (empty($valid_token)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            El enlace de recuperación no es válido o ha expirado.
            <a href="?action=forgot" class="alert-link">Solicita uno nuevo</a>.
        </div>
        <?php else: ?>

        <div class="card">
            <div class="card-body p-4">
                <form method="post" action="?action=reset">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" id="pw" name="password" class="form-control" required
                               placeholder="••••••••" autocomplete="new-password" autofocus>
                        <ul class="list-unstyled small mt-2 mb-0" id="pw-checks">
                            <li data-check="len"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                            <li data-check="low"><i class="bi bi-circle"></i> Una letra minúscula (a-z)</li>
                            <li data-check="up"><i class="bi bi-circle"></i> Una letra mayúscula (A-Z)</li>
                            <li data-check="num"><i class="bi bi-circle"></i> Un número (0-9)</li>
                            <li data-check="sp"><i class="bi bi-circle"></i> Un carácter especial (!@#$%…)</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="bi bi-check-lg"></i> Cambiar contraseña
                    </button>
                </form>
            </div>
        </div>
        <?php endif ?>

        <p class="text-center mt-3 small text-muted">
            <a href="?action=login">← Volver al inicio de sesión</a>
        </p>
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
    document.querySelector('form').addEventListener('submit', (e) => {
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
