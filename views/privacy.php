<?php
$title = 'Política de privacidad — Wisenomy';
$hide_nav_breadcrumb = true;
ob_start();
?>
<div class="card">
    <div class="card-body p-4">
        <h2 class="mb-3">Política de privacidad</h2>
        <p class="text-muted small">Última actualización: <?= date('Y-m-d') ?></p>

        <div class="alert alert-info small">
            <i class="bi bi-info-circle"></i>
            Wisenomy es un proyecto open-source autoalojable. Esta política describe el comportamiento
            del software <em>por defecto</em>. Si estás usando una instancia desplegada por un tercero,
            la responsabilidad sobre tus datos recae en quien la opere.
        </div>

        <h5 class="mt-4">Datos que almacena la aplicación</h5>
        <ul>
            <li><strong>Cuenta:</strong> nombre, email y contraseña (esta última cifrada con bcrypt, nunca en texto plano).</li>
            <li><strong>Uso:</strong> grupos creados, participantes, transacciones, miembros e invitaciones.</li>
            <li><strong>Seguridad:</strong> dirección IP e intentos de inicio de sesión recientes (protección anti-fuerza-bruta).</li>
        </ul>
        <p>
            Todos los datos se guardan en una base de datos local (SQLite por defecto, opcionalmente PostgreSQL
            en despliegues). No hay base de datos central compartida entre instancias.
        </p>

        <h5 class="mt-4">Lo que la aplicación NO hace</h5>
        <ul>
            <li>No envía datos personales a terceros con fines comerciales.</li>
            <li>No usa cookies de seguimiento, ni Google Analytics, ni píxeles publicitarios.</li>
            <li>No realiza perfilado ni decisiones automatizadas sobre los usuarios.</li>
            <li>No vende ni cede datos a anunciantes (no hay anunciantes).</li>
        </ul>

        <h5 class="mt-4">Servicios externos invocados</h5>
        <ul>
            <li>
                <strong>API de tipos de cambio:</strong>
                <a href="https://frankfurter.dev" target="_blank" rel="noopener">api.frankfurter.dev</a>
                (datos del Banco Central Europeo). Solo se envía la divisa consultada, ningún dato personal.
            </li>
            <li>
                <strong>CDN de Bootstrap:</strong> <code>cdn.jsdelivr.net</code> para servir CSS/JS. Tu
                navegador realiza peticiones a este CDN, que puede registrar la IP y el User-Agent.
                Si esto te preocupa, autoaloja los recursos estáticos.
            </li>
        </ul>

        <h5 class="mt-4">Cookies</h5>
        <p>
            Wisenomy utiliza <strong>una única cookie técnica de sesión</strong> (<code>PHPSESSID</code>),
            imprescindible para mantenerte identificado mientras navegas. Es <code>HttpOnly</code> y
            <code>SameSite=Lax</code>, y se transmite solo por HTTPS en despliegues. Conforme al
            artículo 22.2 de la LSSI no requiere consentimiento previo.
        </p>

        <h5 class="mt-4">Conservación y eliminación</h5>
        <p>
            Tus datos se conservan mientras tu cuenta esté activa. Desde tu perfil puedes:
        </p>
        <ul>
            <li>Cambiar tu nombre y contraseña en cualquier momento.</li>
            <li>Eliminar tu cuenta de forma permanente. Esta acción borra en cascada todos los grupos
                que creaste, sus participantes, transacciones, invitaciones y tu membresía en grupos
                ajenos. La eliminación es inmediata e irreversible.</li>
        </ul>
        <p>
            Los registros de intentos de inicio de sesión se purgan automáticamente tras 24 horas.
        </p>

        <h5 class="mt-4">Tus derechos</h5>
        <p>
            Si usas una instancia operada por un tercero, este es el responsable del tratamiento y
            puedes ejercer ante él los derechos de acceso, rectificación, supresión, oposición,
            limitación y portabilidad reconocidos por el RGPD. En las instancias públicas, los datos
            de contacto del responsable se publicarán en el aviso legal correspondiente.
        </p>
        <p>
            En la instancia oficial del proyecto, los derechos pueden ejercerse desde la propia
            interfaz (edición de perfil y eliminación de cuenta). Para cualquier otra cuestión, abre
            una <em>issue</em> en el repositorio.
        </p>

        <h5 class="mt-4">Medidas de seguridad implementadas</h5>
        <ul>
            <li>Contraseñas cifradas con bcrypt.</li>
            <li>Protección CSRF en todos los formularios.</li>
            <li>Limitación de intentos de inicio de sesión (5 fallos / 15 min).</li>
            <li>Cookies de sesión <code>HttpOnly</code> + <code>SameSite=Lax</code>.</li>
            <li>Cabeceras de seguridad: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS (en HTTPS).</li>
            <li>Política de contraseñas fuertes (mín. 8 caracteres, mayúsculas, minúsculas, número, especial).</li>
        </ul>

        <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
