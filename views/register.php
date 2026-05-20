<?php
$title  = 'Crear cuenta — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="navbar-brand-grad" style="font-size:2rem">
                <i class="bi bi-cash-coin"></i> Wisenomy
            </h1>
            <p class="text-muted">Crea tu cuenta para empezar</p>
        </div>

        <?php if (!empty($verification_sent)): ?>
        <div class="alert alert-success" role="alert">
            <h5 class="alert-heading"><i class="bi bi-envelope-check"></i> ¡Casi listo!</h5>
            <p class="mb-2">
                Te hemos enviado un email a
                <strong><?= htmlspecialchars($sent_email) ?></strong>
                con un enlace para verificar tu cuenta.
            </p>
            <p class="mb-0 small">
                Revisa también la carpeta de spam. El enlace caduca en 48 horas.
                <a href="?action=login" class="alert-link">Ir al inicio de sesión</a>.
            </p>
        </div>
        <?php else: ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif ?>

        <div class="card">
            <div class="card-body p-4">
                <form method="post" action="?action=register">
            <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required autofocus maxlength="80"
                               value="<?= htmlspecialchars($name ?? '') ?>"
                               placeholder="Tu nombre">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               placeholder="tu@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" id="pw" name="password" class="form-control" required
                               placeholder="••••••••" autocomplete="new-password">
                        <ul class="list-unstyled small mt-2 mb-0" id="pw-checks">
                            <li data-check="len"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                            <li data-check="low"><i class="bi bi-circle"></i> Una letra minúscula (a-z)</li>
                            <li data-check="up"><i class="bi bi-circle"></i> Una letra mayúscula (A-Z)</li>
                            <li data-check="num"><i class="bi bi-circle"></i> Un número (0-9)</li>
                            <li data-check="sp"><i class="bi bi-circle"></i> Un carácter especial (!@#$%…)</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="bi bi-person-plus"></i> Crear cuenta
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 small text-muted">
            ¿Ya tienes cuenta? <a href="?action=login" class="fw-semibold">Iniciar sesión</a>
        </p>
        <?php endif ?>
    </div>
</div>
<style>
#pw-checks li { color: #94a3b8; transition: color 0.15s; }
#pw-checks li.ok { color: #059669; }
#pw-checks li.ok .bi::before { content: "\f26b"; } /* bi-check-circle-fill */
</style>
<script>
(function () {
    const pw = document.getElementById('pw');
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
