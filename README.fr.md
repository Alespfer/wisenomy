# Wisenomy

> 🌍 Read this in [English](README.md) · [Español](README.es.md)

**Une alternative à Splitwise que vous pouvez lire en une après-midi.**
Pas de framework. Pas de Composer. Pas de Node. Pas d'étape de build. ~3 500 lignes de PHP qui font tout le travail.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4)
![SQLite / PostgreSQL](https://img.shields.io/badge/DB-SQLite%20%2F%20PostgreSQL-003B57)
![Licence : MIT](https://img.shields.io/badge/Licence-MIT-yellow.svg)
![Zéro dépendance](https://img.shields.io/badge/d%C3%A9pendances-z%C3%A9ro-success)

🚀 **Démo en ligne :** [wisenomy.app](https://wisenomy.app) · 🐳 **Auto-hébergement :** `docker compose up`

<p align="center">
  <img src="docs/screenshot.png" alt="Vue de groupe Wisenomy avec soldes, règlement et transactions" width="720">
</p>

---

## Pourquoi ce projet existe

Les applications web modernes exigent beaucoup : `npm install`, transpileurs, ORM, trois couches de bundlers. Wisenomy fait le pari inverse :

- **Zéro dépendance à l'exécution.** Pas de `vendor/`, pas de `node_modules/`. Tout le code est à un `git clone` de distance.
- **Un seul langage de bout en bout.** PHP pour le routage, la persistance et les templates. Pas de changement de contexte, pas de framework JS.
- **Forkable en deux jours en toute confiance.** ~3 500 lignes au total. N'importe quel développeur PHP peut auditer la sécurité, comprendre le modèle de données et livrer une fonctionnalité sur mesure sans lire la documentation.
- **Hébergeable pour 14 €/an.** Cloudflare DNS (gratuit) + Railway PHP+Postgres (5 $/mois) + Resend pour les e-mails transactionnels (3 000/mois gratuits) + votre domaine.
- **Choix durables.** SQLite pour le développement, PostgreSQL pour la production, SQL brut via PDO, HTML rendu côté serveur, Bootstrap via CDN. Des technologies qui fonctionneront encore en 2036.

Ce n'est pas la réponse à tous les projets. C'est la réponse quand vous voulez *posséder* votre stack au lieu de l'emprunter.

---

## Démarrage rapide

### Auto-hébergement avec Docker (la voie facile)

```bash
git clone https://github.com/Alespfer/wisenomy.git
cd wisenomy
docker compose up -d
open http://localhost:8080
```

Cela lance PHP 8.4 + PostgreSQL avec des volumes persistants. Pour arrêter : `docker compose down`.

### Exécution locale sans Docker

Nécessite PHP 8.1+ avec PDO SQLite (inclus par défaut).

```bash
git clone https://github.com/Alespfer/wisenomy.git
cd wisenomy
php -S localhost:8000
```

La base SQLite se crée automatiquement dans `data/app.sqlite` à la première requête.

### MAMP / XAMPP

Déposez le dossier dans `htdocs/` puis ouvrez `http://localhost:8888/wisenomy/`.

---

## Fonctionnalités

### Gestion des dépenses
- **5 types de transactions** : partage égal, parts personnalisées, prise en charge complète, cadeau, règlement.
- **Algorithme de règlement minimal** — réduction gloutonne des dettes mutuelles.
- **Plus de 30 devises** avec taux de change en direct de la BCE ([API Frankfurter](https://frankfurter.dev)) — cache de 12 h avec repli en cas de panne.
- **Arithmétique en centimes entiers** — aucune erreur d'arrondi en virgule flottante, jamais.
- **Sous-titre EUR** sous les montants non-EUR pour référence rapide.

### Groupes et collaboration
- **Multi-utilisateur** avec isolation stricte des données par utilisateur.
- **Transfert de propriété** du groupe à un autre membre.
- **Invitation par lien** avec TTL et nombre maximal d'utilisations configurables, révocable.
- **Invitation par e-mail** pour les utilisateurs déjà inscrits.

### Visibilité
- **Fil d'activité** par groupe, paginé.
- **Statistiques** par catégorie, payeur et mois avec filtre par plage de dates.
- **Tableau de transactions triable** avec filtres par payeur, catégorie, type et date.
- **Pagination de 30 par page** pour les groupes au long historique.

### Portabilité des données
- **Export / import CSV** pour les 5 types de transactions — UTF-8 avec BOM pour Excel.
- **Aller-retour sûr** : export → import produit des données identiques.

### Compte et sécurité
- Politique de mots de passe forts : 8+ caractères, majuscule, minuscule, chiffre, caractère spécial.
- Hachage **bcrypt**, jetons **CSRF** sur chaque POST, **limitation de débit** (5 échecs / 15 min).
- Cookies de session `HttpOnly` + `SameSite=Lax` + `Secure` en HTTPS.
- En-têtes de sécurité : CSP, X-Frame-Options, HSTS, Referrer-Policy.
- **Vérification d'e-mail** à l'inscription.
- Réinitialisation de mot de passe via lien tokenisé à usage unique.
- **Suppression de compte conforme RGPD** avec cascade et résumé préalable.

---

## Structure du projet

```
wisenomy/
├── index.php             # Contrôleur frontal / routeur
├── schema.sql            # Schéma SQLite (auto-appliqué à la première requête)
├── schema.postgres.sql   # Schéma PostgreSQL (utilisé si DATABASE_URL est défini)
├── lib/
│   ├── config.php        # Helper env() + détection proxy/HTTPS
│   ├── db.php            # Bootstrap PDO, détection de driver, helpers SQL
│   ├── auth.php          # Inscription, login, sessions, CSRF, vérification e-mail
│   ├── mail.php          # Intégration Resend avec repli en développement
│   ├── repo.php          # CRUD + statistiques
│   ├── currency.php      # Taux de change Frankfurter avec cache
│   ├── settlement.php    # Algorithme de minimisation de dettes
│   ├── csv.php           # Import / export
│   ├── invites.php       # Liens d'invitation aux groupes
│   └── activity.php      # Journal d'activité par groupe
├── views/                # Templates (un fichier par route)
├── data/                 # SQLite + logs (ignoré par git)
├── Dockerfile            # Image single-stage
└── docker-compose.yml    # App + PostgreSQL
```

---

## Configuration

L'application fonctionne sans configuration en développement (SQLite + e-mails écrits en fichier). Pour la production, définissez les variables d'environnement (voir [`.env.example`](.env.example)) :

| Variable | Rôle |
|---|---|
| `APP_ENV` | `production` masque l'affichage des erreurs, `development` les affiche |
| `APP_URL` | URL canonique utilisée dans les liens des e-mails sortants |
| `TRUST_PROXY` | À mettre à `1` derrière Cloudflare/Railway pour respecter `X-Forwarded-*` |
| `DATABASE_URL` | Si défini, l'application utilise PostgreSQL au lieu de SQLite |
| `RESEND_API_KEY` | Si défini, les e-mails passent par [Resend](https://resend.com) ; sinon ils sont écrits dans `data/mail.log` |
| `MAIL_FROM` | Adresse expéditrice (doit être sur un domaine vérifié chez Resend) |
| `REQUIRE_EMAIL_VERIFICATION` | `0` pour désactiver la vérification d'e-mail obligatoire |

---

## Comment l'instance officielle est déployée

[`wisenomy.app`](https://wisenomy.app) tourne sur :

- **App + PostgreSQL** : [Railway](https://railway.com) (~5 $/mois).
- **E-mails** : [Resend](https://resend.com) (3 000 e-mails/mois gratuits).
- **DNS, HTTPS, CDN, WAF** : [Cloudflare](https://cloudflare.com) (gratuit).

Coût total : ~5 $/mois + 14 €/an pour le domaine. Vous pouvez répliquer ce stack pour n'importe quel projet perso avec les mêmes chiffres.

---

## Feuille de route

- [ ] Authentification à deux facteurs (TOTP).
- [ ] Interface multilingue (actuellement uniquement en espagnol).
- [ ] Sauvegardes PostgreSQL automatisées vers S3/R2.
- [ ] Application mobile native (le web est déjà responsive).
- [ ] Export du fil d'activité en PDF.

---

## Contribuer

Les pull requests sont bienvenues. Pour des changements importants, ouvrez d'abord une issue pour discuter de la direction.

- **Bugs et demandes de fonctionnalités** : ouvrez une issue.
- **Vulnérabilités de sécurité** : voir [SECURITY.md](SECURITY.md) — merci de signaler en privé.
- **Style de code** : respectez la doctrine LAGOM — petit, explicite, sans dépendances.

---

## Licence

[MIT](LICENSE) — faites ce que vous voulez, sans garantie.
Si vous le forkez et le déployez publiquement, merci de renommer votre fork pour éviter toute confusion avec l'original.

---

## Remerciements

- [Frankfurter](https://frankfurter.dev) — taux de change gratuits de la Banque centrale européenne.
- [Bootstrap](https://getbootstrap.com) — framework UI.
- [Bootstrap Icons](https://icons.getbootstrap.com) — jeu d'icônes.
- [Resend](https://resend.com) — API d'e-mail transactionnel avec un généreux plan gratuit.
