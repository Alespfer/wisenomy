<?php
$title = ($verify_ok ?? false) ? 'Email verificado — Wisenomy' : 'Enlace no válido — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="navbar-brand-grad" style="font-size:2rem">
                <i class="bi bi-cash-coin"></i> Wisenomy
            </h1>
        </div>

        <?php if (!empty($verify_ok)): ?>
        <div class="card">
            <div class="card-body p-4 text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
                <h4 class="mt-3">Email verificado</h4>
                <p class="text-muted">
                    Tu cuenta ya está activa. Ahora puedes iniciar sesión y empezar a usar Wisenomy.
                </p>
                <a href="?action=login" class="btn btn-brand">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body p-4 text-center">
                <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem"></i>
                <h4 class="mt-3">Enlace no válido</h4>
                <p class="text-muted">
                    El enlace de verificación es incorrecto o ha caducado. Solicita uno nuevo desde
                    la pantalla de inicio de sesión.
                </p>
                <a href="?action=login" class="btn btn-outline-secondary">
                    Ir al inicio de sesión
                </a>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
