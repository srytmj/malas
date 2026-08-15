<div align="center">
  <img src="public/images/favicon/favicon-512.png" alt="Malas logo" width="96" height="96">

  # Malas
  ### Manga Library Admin System

  A self-hosted library manager for your personal manga, manhwa, manhua & light novel collection.

  [![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
  [![React](https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black)](https://react.dev)
  [![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?style=flat-square&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
  [![Inertia.js](https://img.shields.io/badge/Inertia.js-v2-9553E9?style=flat-square)](https://inertiajs.com)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
  [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](#license)

  **Language:** 🇬🇧 English (you're here) · [🇮🇩 Bahasa Indonesia](README.id.md)
</div>

---

> Built for collectors who need to track which volumes they own, their condition, and who's borrowing what — no manual spreadsheets, no monthly subscription, and your data stays yours.

## ✨ Key Features

<table>
<tr><td width="50%" valign="top">

**Collection & Catalog**
- 📚 Catalog + personal collection, add volumes via range syntax (`1,2,5-9,11,12`)
- 👁️ Per-volume read tracking — mark read/unread, auto progress in the collection table
- ➕ Quick read-progress and per-format volume-count steppers — available from the collection list itself, no need to open a detail page
- 🗂️ Custom collection groups (MDList/MangaDex-style) — create named lists (e.g. "RomCom") and add manga from your collection to them, one manga can belong to multiple groups
- ⭐ Personal review & rating (-10 to +10, MyAnimeList-style)
- 💌 Wishlist — series you want to read but haven't collected yet
- 🔁 Volume lending — who borrowed it, due date, automatic overdue status

**Import & Data**
- 🔎 Import metadata from [AniList](https://anilist.co) (GraphQL) — title, synopsis, genres, authors, score
- 📖 Import light novels from RanobeDB — author/illustrator natively split
- ⚡ Batch import: filter by genre + year + sort by popularity, multi-select import
- 🔍 Searchable, multi-select genre filter on the Catalog page (OR-match)

</td><td width="50%" valign="top">

**User Experience**
- 🎲 Recommendations & "Surprise Me" based on genre overlap with your collection
- ⌘K Global search / Command Palette — instant navigation
- 📊 Dashboard with charts (Recharts), not just raw numbers
- 🧵 "Genre Taste" word cloud + AI funfact (free via Puter.js, or bring your own Gemini/OpenAI/Claude)
- 👥 Opt-in public profile + follow + user directory
- 🔀 Multi-account switching — link and quick-switch between accounts in the same browser session
- ↩️ Undo button on toasts for reversible actions
- 🌗 Light/Dark/System theme, 🌐 three languages (id/en/ja) — UI, validation messages, *and* controller flash messages are all fully translated

**Admin & Infrastructure**
- 🔐 SSO login (PKCE OAuth2) *or* email magic link — two peer login methods
- 🗄️ Flexible storage (Local / S3-compatible) configured from the UI, no `.env` editing
- 💾 Database backup & restore from the admin UI
- 🧑‍✈️ `super_admin` > `admin` > `user` roles, database-driven drag-and-drop menus

</td></tr>
</table>

Full feature list in [`CLAUDE.md`](CLAUDE.md) under "Fitur yang Sudah Ada" (Indonesian, but code references are language-agnostic).

---

## 🧱 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 |
| Frontend bridge | Inertia.js v2 |
| Frontend UI | React 19 + TypeScript 5 |
| UI components | shadcn/ui (Base UI-based) |
| Styling | Tailwind CSS v4 |
| Bundler | Vite |
| Database | SQLite (dev) / MySQL 8+ (prod) |
| Auth/Roles | Spatie Laravel Permission |
| SSO Auth | whitearchive.id (PKCE OAuth2) |
| External APIs | AniList GraphQL, RanobeDB REST |
| AI (client-side) | Puter.js (default, free) — or Gemini/OpenAI/Claude via admin UI |
| Email | Resend (configured via admin UI, not `.env`) |
| Localization | react-i18next (id/en/ja) |
| Drag & drop | @dnd-kit |

Full architecture details: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

### Role hierarchy

```
super_admin > admin > user
```

Access is gated on two layers: Spatie Role (resource level) + `CheckMenuAccess` middleware (route level).

---

## 🚀 Local Setup (Development)

Requirements: **PHP 8.2+**, **Composer**, **Node.js 20+**, **npm**.

```bash
git clone <repo-url> malas
cd malas
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run dev
```

Run the Laravel server in a separate terminal:

```bash
php artisan serve
```

Open `http://localhost:8000`.

### SSO login during development

Login goes through whitearchive.id SSO — register your application at `sso.whitearchive.id/dashboard/applications` to get `SSO_CLIENT_ID` and `SSO_CLIENT_SECRET`, then set them in `.env`:

```env
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI=http://localhost:8000/auth/callback
```

Don't want to set up SSO yet? Use the CLI emergency-access path (see [Troubleshooting](#-troubleshooting)) to log in directly as `super_admin`.

### Storage during development

The default `local` driver works out of the box with no extra configuration. To switch to S3-compatible storage (Cloudflare R2, etc.), open `/admin/settings/storage` after logging in as `super_admin` — no `.env` editing required.

---

## 📦 Production Deployment

Two methods available — automated via `deploy.sh` or manual step-by-step. Full guide including AWS EC2, Azure VM, and bare-metal/VPS setup: **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)**.

Update code on an already-live server:

```bash
bash update.sh
```

This script runs `git pull`, rebuilds dependencies/frontend only when needed, runs new migrations, and rebuilds caches — without touching existing data.

---

## 🧪 Testing

```bash
php artisan test
npx tsc --noEmit
```

---

## 🆘 Troubleshooting

**Can't log in at all / whitearchive.id (SSO) is down?**

```bash
php artisan sso:emergency-login super_admin
```

Issues a one-time login link straight from the CLI — no need to wait for SSO to recover or for an email to arrive. The argument can be a role (`super_admin`/`admin`/`user`) or a specific email/username (e.g. `php artisan sso:emergency-login admin@yourdomain.com`). The command asks for confirmation before issuing the link, and if multiple users share the same role, it gives you an interactive picker.

Regular users (not just admins) can also request their own one-time magic link via email — click "Login" on the landing page → choose "Login with Email". This requires an Email provider (Resend) already configured under `/admin/settings` → Email tab.

Fuller troubleshooting guide (Nginx 502s, failed migrations, storage permissions, etc.): **[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md#troubleshooting)**.

---

## 🗺️ Known Gaps (Backlog)

Being upfront about what's *not* there yet, so nothing catches you off guard:

- **Advanced AniList batch-import filters** — multi-select genre, tag filter (`tag_in`), status filter (`status_in`). Verified working against the live AniList API, not yet built into the UI.
- **Activity feed on public profiles** (Steam-style) — public profiles + follow already exist, the activity feed doesn't yet.
- **"Genre Taste" badges/labels** ("Genre Explorer" vs "Genre Loyalist") — deferred, would reuse the same data as the word-cloud feature.
- **Manual visual verification of the `LoginMethodDialog` modal** — code is `tsc`-clean and reviewed, but has never been clicked through in a real browser (see `docs/PHASES.md` Phase 18 for why).

See [`CLAUDE.md`](CLAUDE.md) under "Belum dikerjakan (backlog)" and [`docs/PHASES.md`](docs/PHASES.md) for full history and context per item.

---

## 📚 Documentation

| Document | Contents |
|---------|-----|
| [`CLAUDE.md`](CLAUDE.md) | Coding rules, folder structure, mandatory conventions for contributions |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Database schema, folder structure, request lifecycle, authorization flow |
| [`docs/PHASES.md`](docs/PHASES.md) | Development phase log from the start to now |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Deploy & update walkthrough — automated and manual |
| [`docs/prd.md`](docs/prd.md) | Product requirements — background, personas, feature specs |
| [`docs/RANOBEDB_INTEGRATION.md`](docs/RANOBEDB_INTEGRATION.md) | API research + development plan for RanobeDB light novel import |
| [`CHANGELOG.md`](CHANGELOG.md) | Notable changes, by date |

## 📄 License

MIT

<div align="right"><a href="#malas">⬆ back to top</a></div>
