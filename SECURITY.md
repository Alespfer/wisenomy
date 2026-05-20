# Política de seguridad

## Reportar una vulnerabilidad

Si descubres un fallo de seguridad en Wisenomy, **no lo publiques en una issue pública**.
Escríbeme directamente a:

📧 **alberto.espfer@gmail.com**

Incluye en el reporte:

- Descripción del problema.
- Pasos para reproducirlo.
- Versión / commit en el que lo has detectado.
- Impacto estimado.

Intentaré responder en un plazo razonable y publicar una corrección antes de divulgar
los detalles. Como agradecimiento, se te mencionará en el changelog si así lo deseas.

## Versiones soportadas

Wisenomy es un proyecto personal sin contrato de soporte. Solo se corrigen vulnerabilidades
en la rama `main`. No hay parches retroactivos para forks o despliegues antiguos.

## Alcance

Reportes relevantes:

- Inyecciones (SQL, XSS, command, etc.).
- Bypass de autenticación o autorización.
- Exposición no autorizada de datos de otros usuarios.
- CSRF efectivos.
- Vulnerabilidades en la gestión de sesiones o tokens de recuperación.

Reportes fuera de alcance:

- Configuración insegura de instancias desplegadas por terceros.
- Ataques que requieran acceso físico al servidor.
- Reportes generados automáticamente por escáneres sin prueba de concepto.
