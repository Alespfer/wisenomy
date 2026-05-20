# Wisenomy

> 🌍 Read this in [Español](README.es.md)

A self-hostable, zero-dependency PHP web app for splitting shared expenses among groups of people — a personal, open-source alternative to Splitwise.

The name comes from the Greek *nómos* (the rule that distributes). Wisenomy keeps the math wise so the conversations stay friendly.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4)
![SQLite / PostgreSQL](https://img.shields.io/badge/DB-SQLite%20%2F%20PostgreSQL-003B57)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![No dependencies](https://img.shields.io/badge/dependencies-none-success)

🚀 **Live demo:** [wisenomy.app](https://wisenomy.app)

---

## Features

### Expense management
- **5 transaction types**: equal split, custom shares, full charge to one person, gift, settlement.
- **Minimum-payment settlement algorithm** — greedy reduction of mutual debts.
- **30+ currencies** with live FX from the ECB ([Frankfurter API](https://frankfurter.dev)) — cached 12h with stale fallback.
- **Integer-cents arithmetic** — no floating-point rounding errors, ever.
- **EUR subtitle** under non-EUR amounts for quick reference.
- **Categories, notes, and date** on every transaction.

### Groups & collaboration
- **Multi-user** with per-user data isolation.
- **Group ownership transfer** to another member.
- **Invite by link** with configurable TTL and max-uses, copy-to-clipboard, revocable.
- **Email invitations** for pre-registered users.

### Visibility
- **Activity feed** per group (paginated) — who added what and when.
- **Statistics** by category, payer, and month, with date-range filter.
- **Sortable transaction table**, payer search, date filters.

### Data portability
- **CSV export/import** for all 5 transaction types — UTF-8 with BOM for Excel.
- **Roundtrip-safe** (export → import produces identical data).

### Account & security
- **Strong password policy**: 8+ chars, uppercase, lowercase, digit, special.
- **bcrypt** hashing, **CSRF** tokens on every POST, **rate limiting** (5 fails / 15 min).
- **Secure session cookies** (`HttpOnly` + `SameSite=Lax`).
- **Security headers**: CSP, X-Frame-Options, HSTS (on HTTPS), Referrer-Policy.
- **Password reset** via tokenized link (1h TTL, single-use).
- **GDPR-friendly account deletion** with cascade and pre-deletion summary.

---

## Quick start

### Requirements
- PHP **8.1+** with PDO SQLite extension (bundled by default).
- A web server: Apache, nginx, or `php -S` for local development.
- No Composer, no Node, no build step.

### Run it locally

```bash
# Clone
git clone https://github.com/<your-user>/wisenomy.git
cd wisenomy

# Start the built-in PHP server
php -S localhost:8000

# Open in your browser
open http://localhost:8000
```

The SQLite database is created automatically in `data/app.sqlite` on first request.

### MAMP / XAMPP

Drop the folder into `htdocs/` and open `http://localhost:8888/wisenomy/` (or the port your stack uses).

---

## Project structure

```
wisenomy/
├── index.php             # Front controller / router
├── schema.sql            # SQLite schema (auto-applied on first request)
├── lib/
│   ├── db.php            # PDO bootstrap
│   ├── auth.php          # Registration, login, sessions, CSRF, password reset
│   ├── repo.php          # CRUD + stats
│   ├── currency.php      # Frankfurter FX with caching
│   ├── settlement.php    # Debt-minimization algorithm
│   ├── csv.php           # Import / export
│   ├── invites.php       # Group invitation links
│   └── activity.php      # Group activity log
├── views/                # Templates (one file per route)
└── data/                 # SQLite DB + reset-link log (gitignored)
```

---

## Configuration

Wisenomy works out of the box with no configuration. You may want to tweak:

- **Session cookie** — `index.php` sets `secure` based on `$_SERVER['HTTPS']`. Behind a reverse proxy, set the appropriate `X-Forwarded-Proto` handling.
- **Timezone** — defaults to whatever PHP is configured to. Activity timestamps use ISO-8601 with offset.
- **SQLite location** — change `data/app.sqlite` in `lib/db.php` if needed.

---

## Deployment

The live instance at [wisenomy.app](https://wisenomy.app) runs on:

- **App + PostgreSQL**: [Railway](https://railway.com) (~$5/month).
- **Emails**: [Resend](https://resend.com) (free tier: 3.000/month).
- **DNS, HTTPS, CDN, WAF**: [Cloudflare](https://cloudflare.com) (free).

To self-host, copy `.env.example` to `.env`, fill in the values, and deploy
to any PHP 8.1+ host. The app auto-detects PostgreSQL when `DATABASE_URL`
is set; otherwise it falls back to SQLite.

## Roadmap

- [ ] Two-factor authentication (TOTP).
- [ ] Multi-language UI (currently Spanish only).
- [ ] Automated PostgreSQL backups to S3/R2.
- [ ] Native mobile app (the web is already responsive).
- [ ] Activity-feed export to PDF.

---

## Contributing

Pull requests welcome. For larger changes, open an issue first to discuss the direction.

- **Bugs and feature requests**: open an issue.
- **Security vulnerabilities**: see [SECURITY.md](SECURITY.md) — please report privately.
- **Code style**: stick to the LAGOM doctrine — small, explicit, dependency-free.

---

## License

[MIT](LICENSE) — do whatever you want, no warranty.
If you fork it and deploy publicly, please rename your fork to avoid confusion with the original.

---

## Acknowledgments

- [Frankfurter](https://frankfurter.dev) — free exchange rates from the European Central Bank.
- [Bootstrap](https://getbootstrap.com) — UI framework.
- [Bootstrap Icons](https://icons.getbootstrap.com) — icon set.
