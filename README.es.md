# Wisenomy

> 🌍 Read this in [English](README.md)

**Una alternativa a Splitwise que puedes leer entera en una tarde.**
Sin frameworks. Sin Composer. Sin Node. Sin paso de build. ~3.500 líneas de PHP que hacen el trabajo completo.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4)
![SQLite / PostgreSQL](https://img.shields.io/badge/DB-SQLite%20%2F%20PostgreSQL-003B57)
![Licencia: MIT](https://img.shields.io/badge/Licencia-MIT-yellow.svg)
![Cero dependencias](https://img.shields.io/badge/dependencias-cero-success)

🚀 **Demo en vivo:** [wisenomy.app](https://wisenomy.app) · 🐳 **Autoaloja:** `docker compose up`

<p align="center">
  <img src="docs/screenshot.png" alt="Vista de grupo en Wisenomy con balances, liquidación y transacciones" width="720">
</p>

---

## Por qué existe este proyecto

Las apps web modernas piden mucho: `npm install`, transpiladores, ORMs, tres capas de bundlers. Wisenomy hace la apuesta contraria:

- **Cero dependencias en ejecución.** No hay `vendor/`, no hay `node_modules/`. Todo el código está a un `git clone` de distancia.
- **Un único lenguaje de extremo a extremo.** PHP para enrutado, persistencia y templates. Sin saltos de contexto, sin framework JS.
- **Forkable en dos días con confianza.** ~3.500 líneas totales. Cualquier desarrollador PHP puede auditar la seguridad, entender el modelo de datos y enviar una feature personalizada sin leer documentación.
- **Hospedable por 14 €/año.** Cloudflare DNS (gratis) + Railway PHP+Postgres ($5/mes) + Resend para email transaccional (3.000/mes gratis) + tu dominio.
- **Decisiones duraderas.** SQLite para desarrollo, PostgreSQL para producción, SQL crudo vía PDO, HTML renderizado en servidor, Bootstrap por CDN. Tecnología que seguirá funcionando en 2036.

No es la respuesta para todos los proyectos. Es la respuesta cuando quieres *poseer* el stack en lugar de tomarlo prestado.

---

## Empezar

### Autoaloja con Docker (la vía fácil)

```bash
git clone https://github.com/Alespfer/wisenomy.git
cd wisenomy
docker compose up -d
open http://localhost:8080
```

Levanta PHP 8.4 + PostgreSQL con volúmenes persistentes. Para parar: `docker compose down`.

### Ejecutar en local sin Docker

Requiere PHP 8.1+ con PDO SQLite (incluido por defecto).

```bash
git clone https://github.com/Alespfer/wisenomy.git
cd wisenomy
php -S localhost:8000
```

La base de datos SQLite se crea automáticamente en `data/app.sqlite` en la primera petición.

### MAMP / XAMPP

Copia la carpeta en `htdocs/` y abre `http://localhost:8888/wisenomy/`.

---

## Funcionalidades

### Gestión de gastos
- **5 tipos de transacción**: reparto igual, reparto personalizado, cargo completo, regalo, liquidación.
- **Algoritmo de liquidación mínima** — reducción voraz de deudas mutuas.
- **30+ divisas** con tipos de cambio en vivo del BCE ([API Frankfurter](https://frankfurter.dev)) — caché de 12h con respaldo si la API cae.
- **Aritmética en céntimos enteros** — sin errores de redondeo de coma flotante.
- **Subtítulo en EUR** bajo importes en otras divisas.

### Grupos y colaboración
- **Multiusuario** con aislamiento estricto de datos por usuario.
- **Transferencia de propiedad** del grupo a otro miembro.
- **Invitación por enlace** con TTL y usos máximos configurables, revocable.
- **Invitación por email** para usuarios ya registrados.

### Visibilidad
- **Feed de actividad** por grupo, paginado.
- **Estadísticas** por categoría, pagador y mes con filtro de fechas.
- **Tabla de transacciones ordenable** con filtros por pagador, categoría, tipo y fecha.
- **Paginación de 30 por página** para grupos con histórico largo.

### Portabilidad
- **Exportar / importar CSV** para los 5 tipos de transacción — UTF-8 con BOM para Excel.
- **Roundtrip seguro**: exportar → importar produce datos idénticos.

### Cuenta y seguridad
- Política de contraseñas fuertes: 8+ caracteres, mayúscula, minúscula, número, especial.
- Hashing **bcrypt**, tokens **CSRF** en cada POST, **rate limiting** (5 fallos / 15 min).
- Cookies de sesión `HttpOnly` + `SameSite=Lax` + `Secure` en HTTPS.
- Cabeceras de seguridad: CSP, X-Frame-Options, HSTS, Referrer-Policy.
- **Verificación de email** obligatoria en el registro.
- Recuperación de contraseña mediante enlace tokenizado de un solo uso.
- **Eliminación de cuenta compatible con RGPD** con borrado en cascada y resumen previo.

---

## Estructura del proyecto

```
wisenomy/
├── index.php             # Controlador frontal / router
├── schema.sql            # Schema SQLite (auto-aplicado en la primera petición)
├── schema.postgres.sql   # Schema PostgreSQL (usado cuando DATABASE_URL está definida)
├── lib/
│   ├── config.php        # Helper env() + detección de proxy/HTTPS
│   ├── db.php            # Bootstrap PDO, detección de driver, helpers SQL
│   ├── auth.php          # Registro, login, sesiones, CSRF, verificación email
│   ├── mail.php          # Integración Resend con fallback en desarrollo
│   ├── repo.php          # CRUD + estadísticas
│   ├── currency.php      # Tipos de cambio Frankfurter con caché
│   ├── settlement.php    # Algoritmo de minimización de deudas
│   ├── csv.php           # Importar / exportar
│   ├── invites.php       # Enlaces de invitación a grupos
│   └── activity.php      # Log de actividad por grupo
├── views/                # Plantillas (un archivo por ruta)
├── data/                 # SQLite + logs (ignorado por git)
├── Dockerfile            # Imagen single-stage
└── docker-compose.yml    # App + PostgreSQL
```

---

## Configuración

La app funciona sin configuración en desarrollo (SQLite + email a archivo). Para producción, define variables de entorno (ver [`.env.example`](.env.example)):

| Variable | Propósito |
|---|---|
| `APP_ENV` | `production` silencia errores en pantalla, `development` los muestra |
| `APP_URL` | URL canónica usada en enlaces de los emails salientes |
| `TRUST_PROXY` | Ponlo a `1` detrás de Cloudflare/Railway para respetar `X-Forwarded-*` |
| `DATABASE_URL` | Cuando se define, la app usa PostgreSQL en vez de SQLite |
| `RESEND_API_KEY` | Cuando se define, los emails salen por [Resend](https://resend.com); si no, se escriben en `data/mail.log` |
| `MAIL_FROM` | Remitente (debe ser de un dominio verificado en Resend) |
| `REQUIRE_EMAIL_VERIFICATION` | `0` para desactivar la verificación obligatoria por email |

---

## Cómo está desplegada la instancia oficial

[`wisenomy.app`](https://wisenomy.app) corre sobre:

- **App + PostgreSQL**: [Railway](https://railway.com) (~$5/mes).
- **Emails**: [Resend](https://resend.com) (3.000 emails/mes gratis).
- **DNS, HTTPS, CDN, WAF**: [Cloudflare](https://cloudflare.com) (gratis).

Coste total: ~$5/mes + 14 €/año del dominio. Puedes replicar este stack para cualquier proyecto personal con las mismas cifras.

---

## Roadmap

- [ ] Autenticación en dos pasos (TOTP).
- [ ] UI multilenguaje (actualmente solo español).
- [ ] Backups automáticos de PostgreSQL a S3/R2.
- [ ] App móvil nativa (la web ya es responsive).
- [ ] Exportación del feed de actividad a PDF.

---

## Contribuir

Pull requests bienvenidas. Para cambios grandes, abre primero una issue para discutir la dirección.

- **Bugs y peticiones de funcionalidades**: abre una issue.
- **Vulnerabilidades de seguridad**: ver [SECURITY.md](SECURITY.md) — repórtalas en privado.
- **Estilo de código**: respeta la doctrina LAGOM — pequeño, explícito, sin dependencias.

---

## Licencia

[MIT](LICENSE) — haz lo que quieras con el código, sin garantías.
Si lo bifurcas y lo despliegas públicamente, renombra tu fork para evitar confusión con el original.

---

## Agradecimientos

- [Frankfurter](https://frankfurter.dev) — tipos de cambio gratuitos del Banco Central Europeo.
- [Bootstrap](https://getbootstrap.com) — framework CSS.
- [Bootstrap Icons](https://icons.getbootstrap.com) — set de iconos.
- [Resend](https://resend.com) — API de email transaccional con un plan gratuito generoso.
