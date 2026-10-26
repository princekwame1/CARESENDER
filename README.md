# CareSenda

Find specialists, diagnostic tests and health facilities in Accra and Tema — with availability checked by the CareSenda team. Laravel 13 web app with a location-aware dashboard, an admin verification console, and a versioned JSON API for the mobile apps.

## Quick start

```bash
cp .env.example .env && php artisan key:generate
composer install && npm install
php artisan migrate --seed      # creates the admin user and imports the directory
npm run build                   # or `npm run dev` while developing
php artisan serve
```

Admin sign-in: `/login` with `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`.

Re-import data at any time (safe to repeat; admin-entered prices, schedules and availability are preserved):

```bash
php artisan caresenda:import [path/to/export.json(.gz)]
```

## What's inside

| Area | Where |
|---|---|
| Location-aware home dashboard | `HomeController`, `resources/views/home.blade.php`, `partials/nearby.blade.php` |
| Search (web + API share one engine) | `App\Services\DirectorySearch`, `SearchFilters`, `Support\SearchNormalizer` |
| Geolocation | `App\Services\GeoLocator`, `resources/js/modules/location.js` |
| Admin dashboard & phone-verification queue | `/admin`, `App\Http\Controllers\Admin\*` |
| Mobile API v1 | `routes/api.php`, `App\Http\Controllers\Api\V1\*`, docs at `/developers` |
| Data import & area gazetteer | `App\Services\DirectoryImporter`, `config/caresenda.php` |

### How geolocation works

1. The browser asks for location only when the user taps **Detect my location** (or silently, if permission was already granted).
2. Coordinates go to `GET /api/v1/geo/resolve`, which finds the nearest of ~135 Accra/Tema neighbourhoods and checks coverage.
3. The result is kept in `localStorage` and a small `cs_loc` cookie so server-rendered pages can sort by distance; the "near you" panel is swapped in without a page reload.
4. Facilities in the source data have no coordinates, so each is placed at its **neighbourhood centroid** (91% matched) or its city centre. Distances are shown as estimates ("~2.4 km"), and the API exposes `location.precision` (`area` | `city`). Search logs store coordinates rounded to ~1 km.

To improve accuracy, add exact `lat`/`lng` per facility (and set `geo_precision = 'exact'`), or add neighbourhoods to `config/caresenda.php`.

## Team dashboard (`/admin`)

Sign in at `/login` with the admin username from `.env` (`ADMIN_USERNAME`, `ADMIN_PASSWORD`).

| Section | What you can do |
|---|---|
| Overview | Totals, search activity (bots excluded), top searches and areas |
| Facilities | Add, edit, hide/show or delete facilities; Google Maps link and exact location; opening hours day by day; add services by ticking them from the service list, then set prices, doctors and times; log a phone check |
| Services | The central service list (88 services in 26 groups). Add, rename, move between groups or switch off; changes update every facility that offers the service |
| Specialties | The groups for the service list: clinical specialties and test groups. Add, rename (updates every listing), describe, hide, choose which appear on the home page, and upload a photo for their search results |
| Website content | One screen per page (General, Home, About us, How we check, Contact us, Search): every heading and paragraph, contact details, an announcement banner, and every photo (upload, alt text, or reset to default). Paragraphs use the Quill editor; saved HTML is cleaned to safe tags |
| Messages | Contact-form inbox |
| API | Mobile API reference |

Times are entered per day and shown to patients in plain language, e.g. “Mon to Fri, 8am to 5pm · Sat, 9am to 1pm”. The search “Day” filter uses them. Re-importing the dataset never overwrites facilities that have been checked or edited in the dashboard.

## Performance notes

- **Assets:** Vite + Tailwind v4, self-hosted fonts. App JS ≈ 4 KB gzipped, CSS ≈ 9 KB. Leaflet (43 KB gz) is code-split and loaded only when a map scrolls into view. The 2 MB embedded hero PNG became responsive WebP (28–89 KB).
- **Data:** the 33 MB single HTML file is now a 390 KB gzipped dataset imported into indexed tables.
- **Queries:** normalised `search_text` columns; distance ranking runs against a cached facility index in memory, and only the requested page is hydrated. Dashboard aggregates are cached and invalidated by a version bump whenever data changes.
- **HTTP:** static API endpoints send `ETag` + `Cache-Control: max-age=300`; search logging is deferred until after the response; rate limits of 120/min (API) and 10/min (auth).
- **Production:** run `./deploy.sh` (optimised autoloader, `php artisan optimize`). Use Redis for `CACHE_STORE`/`SESSION_DRIVER` and MySQL/Postgres for larger datasets.

## API

Base URL `/api/v1`. Full reference at **`/developers`**.

```
GET  /meta                         filters, common searches, totals
GET  /dashboard?lat=&lng=          mobile home screen in one call
GET  /geo/resolve?lat=&lng=        nearest area + what's within 5 km
GET  /suggest?q=                   type-ahead suggestions
GET  /services?q=&type=&city=&diagnostic=&day=&lat=&lng=&radius=&confirmed=&sort=&page=&per_page=
GET  /facilities  · /facilities/nearby · /facilities/{id} · /services/{id}
GET  /areas · /map/markers
POST /auth/register · /auth/login   → Bearer token (Sanctum)
POST /auth/logout · GET /me · GET|DELETE /me/searches
GET  /admin/stats · PATCH /admin/facilities/{id}   (admin token)
```

## Tests

```bash
php artisan test
```
