# RanobeDB Integration — Light Novel Metadata Import

**Status:** ✅ Implemented (2026-08-01) — see [`PHASES.md`](PHASES.md) Phase 13. This document was written ahead of implementation as research + a development plan; it's kept as reference for the API shape and design decisions below. Actual implementation: `app/Services/RanobeDbService.php`, `app/Http/Controllers/Admin/RanobeDbController.php`, `resources/js/Pages/Admin/RanobeDb/Index.tsx`.

**Source:** [ranobedb.org/api/docs/v0](https://ranobedb.org/api/docs/v0), verified against the live API on 2026-07-26.

---

## 1. What RanobeDB Is

RanobeDB is a community-maintained light novel database (same family/style as VNDB — same query-param conventions, same packed-date encoding). It's the natural light-novel equivalent of what AniList is for manga in MALAS today.

- **API base URL:** `https://ranobedb.org/api/v0`
- **Auth:** None. No API key, no OAuth.
- **License:** Open Database License (ODbL) + Database Contents License — **non-commercial use only**. MALAS is a personal/self-hosted collection tracker, not monetized, so this is compliant — but it rules out ever charging for a MALAS instance that uses this data.
- **Rate limit:** None enforced server-side, but the docs ask to stay under 60 requests/minute. `AniListService` has no explicit throttling either, so this is a new constraint to actually implement (simple sleep/backoff or a request counter).
- **Transport:** Plain REST + query params, JSON responses. No GraphQL query-building like AniList — simpler client code.
- **Coverage:** Not just Japanese light novels — also web novels, and multi-language editions (the same book can have `ja`, `en`, etc. releases with separate ISBNs/dates).

---

## 2. API Reference (condensed, verified against live responses)

### Common types
- `Language` — large union (`ja`, `en`, `zh-Hans`, `zh-Hant`, `ko`, ...).
- Dates are `number` in `YYYYMMDD` form (e.g. `20140124`). **`99999999` is the sentinel for "ongoing / no end date"** — verified live via `/series/3343` (`c_end_date: 99999999` for an ongoing series). Release-level dates also come with a **pre-parsed convenience field** `release_date_parsed: "2014-01-24"` (confirmed via `/book/12040`) — prefer that over hand-parsing the int where it's present.
- Cover images: response gives `image.filename` (e.g. `CREz48RNPpCuZbIs.jpg`); the actual URL is **`https://images.ranobedb.org/{filename}`** (verified by inspecting real `<img>` tags on ranobedb.org — this isn't documented on the API page itself).
- Release `format`: `"digital" | "print" | "audio"`.

### Endpoints

| Endpoint | Purpose | Key params |
|---|---|---|
| `GET /books` | Search individual books (≈ volumes) | `q`, `rl` (release lang), `rf` (format), `staff`, `p` (publisher), `sort`, `page`, `limit` (max 100) |
| `GET /book/{id}` | Full book detail | — |
| `GET /series` | Search series (the "work" spanning many books) | `q`, `pubStatus`, `genresInclude/Exclude`, `tagsInclude/Exclude`, `rl`, `rf`, `staff`, `p`, `sort` |
| `GET /series/{id}` | Full series detail | — |
| `GET /releases` / `/release/{id}` | Specific print/digital/audio editions | `minDate`/`maxDate`, `rf`, `p` |
| `GET /staff` / `/staff/{id}` | Authors, artists, translators, etc. | `q` |
| `GET /publishers` / `/publisher/{id}` | Publisher/imprint info | `q` |
| `GET /tags` | Genre/demographic/content/tag taxonomy | `q` |

### `GET /series/{id}` response (the main import source) — key fields
```
title, title_orig, romaji, romaji_orig, titles[] (per-language, official flag)
description, publication_status ("unknown"|"ongoing"|"completed"|"hiatus"|"stalled"|"cancelled")
start_date, end_date (YYYYMMDD int, 99999999 = ongoing)
books[]        — { id, book_type: "main"|"sub", sort_order, title, image, c_release_date, c_release_dates{lang: date} }
staff[]        — { role_type: "staff"|"author"|"artist"|"editor"|"translator"|"narrator", name, romaji, staff_id }
tags[]         — { id, name, ttype: "content"|"demographic"|"genre"|"tag" }
publishers[]   — { id, name, publisher_type: "publisher"|"imprint" }
child_series[] — { relation_type: "prequel"|"sequel"|"side story"|"main story"|"spin-off"|"parent story"|"alternate version" }
```

### `GET /book/{id}` response (per-volume detail — richer than anything AniList gives MALAS today)
```
title, description, image
editions[] — { staff[]: same role_type breakdown as series, per language edition }
releases[] — { format, lang, isbn13, pages, release_date, release_date_parsed, amazon, bookwalker, rakuten, website }
rating     — { score, count }   ← book-level score, NOT series-level (unlike AniList's series-level score)
series     — back-reference to parent series (id, title, tags)
```

---

## 3. Data Model Mapping → MALAS Schema

MALAS's existing `series` table already has almost everything needed — this is additive, not a schema rework.

| RanobeDB field | MALAS column | Notes |
|---|---|---|
| `series.titles[]` (pick official `en`/`romaji`/`ja`) | `title_romaji`, `title_english`, `title_japanese` | Same "pick from array by lang" logic `AniListService` already does for AniList's `title{romaji,english,native}` object — different shape, same idea. |
| `series.description` | `synopsis` | Direct. |
| `series.publication_status` | `status` | Needs a mapping table — see §5. |
| `series.start_date` / `end_date` | `published_from` / `published_to` | Parse `YYYYMMDD` int; `99999999` → `null`. |
| `series.books.length` (or `c_num_books`) | `total_volumes` | Direct. |
| `series.tags[]` where `ttype:"genre"` | `genres` | Direct — RanobeDB's `ttype` split maps 1:1 onto MALAS's existing genres/themes/demographics split, better than AniList's flatter tag list. |
| `series.tags[]` where `ttype:"demographic"` | `demographics` | Direct. |
| `series.tags[]` where `ttype:"content"` or `"tag"` | `themes` | Reasonable fit, not a perfect 1:1 concept but close enough. |
| `series.staff[]` where `role_type:"author"` | `authors` | Direct. |
| `series.staff[]` where `role_type:"artist"` | **New: `illustrators`?** | See §5 — RanobeDB natively separates author from illustrator, which AniList doesn't. Worth a schema decision. |
| `book.image.filename` (first/main book) | `cover_path` | Via `https://images.ranobedb.org/{filename}`, downloaded through `StorageSettingsService` exactly like AniList covers are today. |
| — (no series-level score in this API) | `score` | Leave null on RanobeDB import, or average `book.rating.score` across books — optional, not required. |
| `series.id` | **New column: `ranobedb_id`** | Mirrors `anilist_id` — unique, nullable, bigint. |
| `series.books[]` (with real `release_date`/`isbn13`/`pages`/`format`) | `volumes` table rows | **This is new capability** — MALAS's `volumes` table already has `isbn`, `published_at`, `type`, `cover_path` columns, but nothing populates them today (AniList only gives a volume *count*, never per-volume data). RanobeDB import could genuinely fill these in for the first time. |

---

## 4. Key Differences from the Existing AniList Integration

Worth internalizing before writing code, since a few assumptions baked into `AniListService`/`AniListController` don't transfer directly:

1. **Three-level model, not two.** AniList: Series → volume count (a number). RanobeDB: Series → Books (≈ volumes) → Releases (print/digital/audio editions of *each* book, each with its own ISBN/pages/date). Deciding how much of that depth to actually import is the biggest open design question (§5).
2. **REST, not GraphQL.** Simpler — no query-building, just `Http::get()` with query params. No new client library needed.
3. **Role-typed staff, not a flat author list.** Genuinely richer than AniList here.
4. **No adult-content flag.** AniList exposes `isAdult` directly; RanobeDB doesn't. Needs a heuristic (§5).
5. **Score lives on books, not series.** If MALAS wants a series-level score from RanobeDB, it has to be derived (e.g. average across `books[].rating.score`), not read directly.
6. **Self-enforced rate limit.** AniList integration has no explicit throttling; this one needs a real one (60 req/min ask).
7. **Non-commercial license clause.** Not a blocker for MALAS as-is, but worth a one-line note in `StorageSettingsService`/wherever covers get attributed, in case this ever gets productized.

---

## 5. Open Decisions (need a product call before implementation starts)

- **Author/Illustrator split:** add a new `illustrators` json column on `series`, or keep dumping both into `authors`? Recommend adding the column — it's a small migration and RanobeDB hands it over for free.
- **Per-volume import depth:** on import, auto-create `Volume` rows from `series.books[]` (populating real `isbn`/`published_at`/`type` for the first time), or keep volumes purely admin/user-managed like today and only import series-level metadata? Recommend: auto-create volumes on import (opt-in toggle, like the existing "Generate" button for AniList series with `total_volumes` set), since this is the actual capability upgrade RanobeDB brings.
- **Print vs digital release when a book has both:** pick one canonical release for `Volume.isbn`/`published_at` (recommend: prefer `print`, since that's what a physical-collector app cares about most), or store both — probably not both, keep it simple.
- **`publication_status` mapping:** RanobeDB's `stalled`/`cancelled` don't map cleanly onto MALAS's `discontinued`. Proposed mapping:
  | RanobeDB | MALAS |
  |---|---|
  | `ongoing` | `publishing` |
  | `completed` | `finished` |
  | `hiatus` | `on_hiatus` |
  | `stalled`, `cancelled` | `discontinued` |
  | `unknown` | `not_yet_published` |
- **`is_adult` inference:** no direct field. Options: check cover `image.nsfw`, or match specific tag names (e.g. a `"Erotica"`/`"Adult"` genre/content tag). Neither is fully reliable — admin should be able to override manually either way (already possible today via the existing `is_adult` toggle).
- **New `series.type` value?** Not needed — `'novel'` already exists in the enum.

---

## 6. Compatibility Verdict

**Yes, compatible — and additive.** This doesn't touch the AniList flow at all; it's a second, parallel import source living alongside it, exactly the way `AniListService` sits next to (the now-removed) `JikanService` used to. Nearly the entire target schema already exists (`series.genres/themes/demographics/authors`, `volumes.isbn/published_at/type`) — the only required migration is one new nullable unique column (`series.ranobedb_id`), everything else is import-mapping logic, not schema change.

---

## 7. Development Plan (mirrors the `PHASES.md` phase format)

Not started — sequence to follow when this gets a "gas":

1. **Migration** — `series.ranobedb_id` (bigint, unique, nullable), placed after `anilist_id`. Optional: `series.illustrators` (json, nullable) if the author/illustrator split is approved.
2. **`RanobeDbService`** (`app/Services/RanobeDbService.php`) — REST client via Laravel's `Http` facade (mirrors `AniListService`'s constructor-injected `StorageSettingsService` pattern for cover downloads). Methods: `searchSeries(string $query)`, `getSeries(int $id)`, `getBook(int $id)`. Self-enforced rate limiting (simple request counter/sleep, since the API has none).
3. **`Admin\RanobeDbController`** (`app/Http/Controllers/Admin/RanobeDbController.php`) — mirrors `AniListController`: `index()` (search UI), `searchJson()`, `import()`. Reuses `SeriesController`'s authorization pattern (`$this->authorize('create', Series::class)`).
4. **Frontend:** `Admin/RanobeDb/Index.tsx` — same card-overlay search/import UX as `Admin/AniList/Index.tsx` (not a Popover — same reason: Base UI's anchor engine doesn't center well). Add sidebar entry via `MenuSeeder.php` (`updateOrCreate`, per `CLAUDE.md`'s menu rule).
5. **Import mapping** — implement the §3 table + §5 decisions: title/lang picking, tag `ttype` → genres/themes/demographics split, staff `role_type` → authors/illustrators split, date parsing (`YYYYMMDD` → date, `99999999` → null), cover download through `StorageSettingsService`.
6. **Optional volume auto-generation** — if approved in §5, extend import to create `Volume` rows from `series.books[]` with real `isbn`/`published_at`/`type`, toggle-gated like the existing volume "Generate" button.
7. **"Sync RanobeDB"** — mirrors the existing "Sync AniList" Popover on Edit Series, for re-pulling metadata into an already-imported novel.
8. **Done criteria** (draft, refine before starting):
   - [ ] Search returns results within the 60 req/min budget without erroring
   - [ ] Import correctly splits genre/demographic/theme tags by `ttype`
   - [ ] Import correctly splits author/illustrator by `role_type` (if §5 approved)
   - [ ] `published_from`/`published_to` handle the `99999999` sentinel correctly (no series shows "ongoing until 9999-99-99")
   - [ ] Duplicate import (same `ranobedb_id`) updates instead of creating a second series
   - [ ] `npx tsc --noEmit` → 0 errors, `php artisan test` → pass
