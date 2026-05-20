# Wisenomy

> 🌍 Read this in [English](README.md)

Aplicación web en PHP, autoalojable y sin dependencias, para dividir gastos compartidos entre grupos de personas. Una alternativa open-source y de proyecto personal a Splitwise.

El nombre viene del griego *nómos* (la norma que reparte). Wisenomy mantiene las cuentas sabias para que la conversación siga siendo amable.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4)
![SQLite](https://img.shields.io/badge/DB-SQLite-003B57)
![Licencia: MIT](https://img.shields.io/badge/Licencia-MIT-yellow.svg)
![Sin dependencias](https://img.shields.io/badge/dependencias-ninguna-success)

---

## Funcionalidades

### Gestión de gastos
- **5 tipos de transacción**: reparto igual, reparto personalizado, cargo completo a una persona, regalo, liquidación.
- **Algoritmo de liquidación mínima** — reducción voraz de deudas mutuas.
- **30+ divisas** con tipos de cambio en vivo del BCE ([API Frankfurter](https://frankfurter.dev)) — caché de 12h con respaldo si la API cae.
- **Aritmética en céntimos enteros** — sin errores de redondeo de coma flotante.
- **Subtítulo en EUR** bajo importes en otras divisas para referencia rápida.
- **Categorías, notas y fecha** en cada transacción.

### Grupos y colaboración
- **Multiusuario** con aislamiento de datos por usuario.
- **Transferencia de propiedad** del grupo a otro miembro.
- **Invitación por enlace** con TTL y usos máximos configurables, copiar al portapapeles, revocable.
- **Invitación por email** para usuarios ya registrados.

### Visibilidad
- **Feed de actividad** por grupo (paginado) — quién hizo qué y cuándo.
- **Estadísticas** por categoría, pagador y mes, con filtro de rango de fechas.
- **Tabla de transacciones ordenable**, búsqueda por pagador, filtros de fecha.

### Portabilidad de datos
- **Exportar / importar CSV** para los 5 tipos de transacción — UTF-8 con BOM para Excel.
- **Roundtrip seguro** (exportar → importar produce datos idénticos).

### Cuenta y seguridad
- **Política de contraseñas fuertes**: 8+ caracteres, mayúsculas, minúsculas, número, especial.
- Hashing con **bcrypt**, tokens **CSRF** en cada POST, **rate limiting** (5 fallos / 15 min).
- **Cookies de sesión seguras** (`HttpOnly` + `SameSite=Lax`).
- **Cabeceras de seguridad**: CSP, X-Frame-Options, HSTS (en HTTPS), Referrer-Policy.
- **Recuperación de contraseña** mediante enlace tokenizado (1h, un solo uso).
- **Eliminación de cuenta compatible con RGPD** con borrado en cascada y resumen previo.

---

## Empezar

### Requisitos
- PHP **8.1+** con la extensión PDO SQLite (incluida por defecto).
- Un servidor web: Apache, nginx, o `php -S` para desarrollo local.
- Sin Composer, sin Node, sin paso de build.

### Ejecutar en local

```bash
# Clonar
git clone https://github.com/<tu-usuario>/wisenomy.git
cd wisenomy

# Arrancar el servidor integrado de PHP
php -S localhost:8000

# Abrir en el navegador
open http://localhost:8000
```

La base de datos SQLite se crea automáticamente en `data/app.sqlite` en la primera petición.

### MAMP / XAMPP

Copia la carpeta dentro de `htdocs/` y abre `http://localhost:8888/wisenomy/` (o el puerto que use tu stack).

---

## Estructura del proyecto

```
wisenomy/
├── index.php             # Controlador frontal / router
├── schema.sql            # Schema SQLite (se aplica solo en la primera petición)
├── lib/
│   ├── db.php            # Bootstrap PDO
│   ├── auth.php          # Registro, login, sesiones, CSRF, reset de contraseña
│   ├── repo.php          # CRUD + estadísticas
│   ├── currency.php      # Tipos de cambio Frankfurter con caché
│   ├── settlement.php    # Algoritmo de minimización de deudas
│   ├── csv.php           # Importar / exportar
│   ├── invites.php       # Enlaces de invitación a grupos
│   └── activity.php      # Log de actividad por grupo
├── views/                # Plantillas (un archivo por ruta)
└── data/                 # DB SQLite + log de reset (ignorado por git)
```

---

## Configuración

Wisenomy funciona sin configuración. Puede que quieras ajustar:

- **Cookie de sesión** — `index.php` activa `secure` según `$_SERVER['HTTPS']`. Detrás de un proxy inverso, configura el manejo apropiado de `X-Forwarded-Proto`.
- **Zona horaria** — usa la configurada en PHP. Los timestamps de actividad usan ISO-8601 con offset.
- **Ubicación de SQLite** — cambia `data/app.sqlite` en `lib/db.php` si lo necesitas.

---

## Roadmap

- [ ] SMTP real para recuperación de contraseña (actualmente escribe en un archivo de log).
- [ ] Verificación de email en el registro.
- [ ] Adaptador PostgreSQL para despliegues alojados.
- [ ] Despliegue de un clic en Railway / Fly.io.
- [ ] UI multilenguaje (actualmente solo español).

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
