<?php
$title  = 'Iniciar sesión — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="navbar-brand-grad" style="font-size:2rem">
                <i class="bi bi-cash-coin"></i> Wisenomy
            </h1>
            <p class="text-muted">Inicia sesión para gestionar tus gastos compartidos</p>
        </div>

        <?php if (!empty($unverified)): ?>
        <div class="alert alert-warning" role="alert">
            <h6 class="alert-heading"><i class="bi bi-envelope-exclamation"></i> Email no verificado</h6>
            <p class="small mb-2">
                Necesitas confirmar tu dirección de correo antes de poder iniciar sesión.
                Revisa tu bandeja (y la carpeta de spam) o solicita un nuevo enlace:
            </p>
            <form method="post" action="?action=resend_verification" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="bi bi-envelope"></i> Reenviar email de verificación
                </button>
            </form>
        </div>
        <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif ?>

        <div class="card">
            <div class="card-body p-4">
                <form method="post" action="?action=login">
            <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               placeholder="tu@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required
                               placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 small text-muted mb-1">
            ¿Sin cuenta? <a href="?action=register" class="fw-semibold">Crear una</a>
        </p>
        <p class="text-center small text-muted">
            <a href="?action=forgot">¿Olvidaste tu contraseña?</a>
        </p>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
