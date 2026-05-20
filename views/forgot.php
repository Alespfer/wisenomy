<?php
$title = 'Recuperar contraseña — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="navbar-brand-grad" style="font-size:2rem">
                <i class="bi bi-key"></i> Wisenomy
            </h1>
            <p class="text-muted">Recupera el acceso a tu cuenta</p>
        </div>

        <?php if (!empty($sent)): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            Si existe una cuenta con ese email, recibirás un enlace para restablecer la contraseña.
            Revisa tu bandeja en los próximos minutos.
        </div>
        <p class="text-center small text-muted">
            <a href="?action=login">Volver al inicio de sesión</a>
        </p>
        <?php else: ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif ?>

        <div class="card">
            <div class="card-body p-4">
                <p class="small text-muted mb-3">
                    Indícanos tu email y te enviaremos un enlace para crear una nueva contraseña.
                    El enlace caduca en 1 hora.
                </p>
                <form method="post" action="?action=forgot">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               placeholder="tu@email.com">
                    </div>
                    <button type="submit" class="btn btn-brand w-100">
                        <i class="bi bi-envelope"></i> Enviar enlace
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 small text-muted">
            <a href="?action=login" class="fw-semibold">← Volver al inicio de sesión</a>
        </p>
        <?php endif ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
