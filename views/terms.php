<?php
$title = 'Términos de uso — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="card">
    <div class="card-body p-4">
        <h2 class="mb-3">Términos de uso</h2>
        <p class="text-muted small">Última actualización: <?= date('Y-m-d') ?></p>

        <h5 class="mt-4">1. Qué es Wisenomy</h5>
        <p>
            Wisenomy es un proyecto personal de software libre distribuido bajo licencia
            <a href="https://opensource.org/licenses/MIT" target="_blank" rel="noopener">MIT</a>.
            No es un servicio comercial. Cualquiera puede descargar el código, ejecutarlo en local,
            modificarlo o desplegarlo en su propio servidor.
        </p>

        <h5 class="mt-4">2. Aceptación</h5>
        <p>
            Al crear una cuenta y utilizar la aplicación aceptas estos términos. Si no estás de
            acuerdo, no la uses.
        </p>

        <h5 class="mt-4">3. Cuenta de usuario</h5>
        <ul>
            <li>Debes tener al menos 14 años para crear una cuenta.</li>
            <li>Eres responsable de mantener la confidencialidad de tu contraseña.</li>
            <li>No suplantes a otras personas al registrarte.</li>
        </ul>

        <h5 class="mt-4">4. Uso aceptable</h5>
        <ul>
            <li>No utilices la aplicación para fines ilícitos.</li>
            <li>No intentes acceder a datos de otros usuarios ni vulnerar la infraestructura.</li>
            <li>No introduzcas contenido difamatorio, ofensivo o que infrinja derechos de terceros
                en los nombres de grupos, participantes o notas.</li>
        </ul>

        <h5 class="mt-4">5. Tus datos y los datos de terceros</h5>
        <p>
            Eres responsable de los datos que introduces. Si registras información de personas que
            no son usuarias de Wisenomy (p. ej. el nombre de un amigo como participante de un grupo),
            asegúrate de que es información que puedes compartir legítimamente y que no se trata de
            categorías especiales de datos personales.
        </p>
        <p>
            Los cálculos de saldos y liquidaciones son <strong>orientativos</strong>. La aplicación
            no interviene en disputas entre miembros de un grupo: la responsabilidad última sobre
            cualquier acuerdo económico es de los usuarios involucrados.
        </p>

        <h5 class="mt-4">6. Sin garantías ni responsabilidad</h5>
        <p>
            El software se ofrece <strong>«tal cual», sin garantía de ningún tipo</strong> (ver
            archivo <code>LICENSE</code>). El autor no será responsable de:
        </p>
        <ul>
            <li>Pérdidas de datos por fallos, ataques o errores de usuario.</li>
            <li>Errores en los cálculos de saldos o liquidaciones.</li>
            <li>Interrupciones del servicio o pérdida de disponibilidad.</li>
            <li>Conflictos o pérdidas económicas derivadas del uso de la aplicación.</li>
        </ul>
        <p>
            Antes de tomar decisiones económicas relevantes, verifica los importes manualmente.
        </p>

        <h5 class="mt-4">7. Cancelación de cuenta</h5>
        <p>
            Puedes eliminar tu cuenta en cualquier momento desde tu perfil. La eliminación es
            inmediata e irreversible: borra todos los grupos que creaste, sus participantes y
            transacciones, tu membresía en grupos ajenos y cualquier enlace de invitación pendiente.
        </p>

        <h5 class="mt-4">8. Disponibilidad del servicio</h5>
        <p>
            El proyecto no garantiza ningún nivel de disponibilidad ni continuidad. Una instancia
            pública puede dejar de funcionar, mudarse de dominio o desaparecer sin previo aviso. Si
            tus datos son importantes para ti, exporta periódicamente tus grupos a CSV desde la
            propia aplicación.
        </p>

        <h5 class="mt-4">9. Modificaciones</h5>
        <p>
            Estos términos pueden actualizarse para reflejar cambios en el software o en su modo de
            distribución. El histórico de cambios queda registrado en el repositorio de GitHub.
        </p>

        <h5 class="mt-4">10. Ley aplicable</h5>
        <p>
            En lo que pueda corresponder, se aplica la legislación española. Dado que el proyecto
            no tiene ánimo de lucro ni titular comercial, las controversias deberían intentar
            resolverse de buena fe antes de acudir a vías judiciales.
        </p>

        <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
