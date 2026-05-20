<?php
$title = 'Aviso legal — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="card">
    <div class="card-body p-4">
        <h2 class="mb-3">Aviso legal</h2>
        <p class="text-muted small">Última actualización: <?= date('Y-m-d') ?></p>

        <h5 class="mt-4">Naturaleza del proyecto</h5>
        <p>
            <strong>Wisenomy</strong> es un proyecto personal de software libre, sin ánimo de lucro y sin
            finalidad comercial. Su código fuente se publica en GitHub bajo licencia
            <a href="https://opensource.org/licenses/MIT" target="_blank" rel="noopener">MIT</a> y puede
            ser inspeccionado, copiado, modificado y autoalojado por cualquier persona.
        </p>
        <p>
            No existe una entidad jurídica detrás del proyecto: no es una empresa, no factura, no presta
            servicios remunerados y no tiene clientes en sentido legal. Cada persona que ejecuta una copia
            del software (en local o en un servidor) lo hace bajo su propia responsabilidad.
        </p>

        <h5 class="mt-4">Objeto del software</h5>
        <p>
            Wisenomy permite registrar y dividir gastos compartidos entre grupos de personas, calculando
            saldos y proponiendo liquidaciones mínimas. Es una herramienta de uso personal o doméstico,
            análoga a una hoja de cálculo compartida.
        </p>

        <h5 class="mt-4">Ausencia de garantías</h5>
        <p>
            El software se ofrece <strong>«tal cual», sin garantía de ningún tipo</strong>, según los
            términos de la licencia MIT. Esto incluye, sin limitación, garantías de comerciabilidad,
            idoneidad para un propósito particular o ausencia de errores. El autor no se hace responsable
            de pérdidas de datos, errores de cálculo, indisponibilidad del servicio o conflictos derivados
            del uso de la aplicación.
        </p>

        <h5 class="mt-4">Uso del código y atribución</h5>
        <p>
            Puedes usar, copiar, modificar y redistribuir el código siempre que conserves el aviso de
            copyright y la licencia originales (ver archivo <code>LICENSE</code> en el repositorio).
            El nombre «Wisenomy» y su logotipo no están registrados como marca, pero te pedimos por
            cortesía que renombres tu fork si lo despliegas públicamente, para evitar confusiones con la
            instancia original.
        </p>

        <h5 class="mt-4">Contacto</h5>
        <p>
            Para consultas, sugerencias o reportes de fallos, abre una <em>issue</em> en el repositorio
            de GitHub. Para reportar problemas de seguridad de forma privada, consulta el archivo
            <code>SECURITY.md</code>.
        </p>

        <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
