# genz-web-apis — Web & Mobile Storefront API

Backend REST API for [`genz-web`](../genz-web) (website) and `genz-app` (mobile).
Acts as the **storefront/BFF**: serves the menu, accounts, cart, checkout, and
online orders. The **menu's source of truth is the RMS** (`genz-rms-apis`) — this
app caches it (see *Menu single source of truth* below).

- **Stack:** Laravel 12, PHP 8.3, Laravel Sanctum (Bearer-token auth).
- **DB:** MySQL database `genz_apis` (created locally; `.env` is set to mysql, not the bundled sqlite).
- **Runs on:** `http://localhost:8000` (web frontend calls `…:8000/api/v1`).
- **PHP:** `php` is on PATH (winget install). Composer at `C:\composer\composer.bat`.

## Run / setup
```bash
php artisan migrate:fresh --seed     # build schema + import menu.json + seed users
php artisan serve --port=8000        # API server
php artisan menu:sync                # pull latest menu from the RMS feed (manual)
```
Seeded logins (password = `password`): `admin@genzfoods.pk` (admin), `customer@genzfoods.pk` (customer).

## Data model (key tables)
- `categories` (type: single|sized, sizes json), `food_items`, `item_variants`
  (label nullable = single price; one row per size for sized items).
- `deals` (+ `deal_extras`, `deal_options` pivot to food_items) — bundle/selection rules.
- `users` (has `role`: customer|admin), `shipping_addresses`.
- `carts`/`cart_items` (exist but the frontend cart is currently client-side/local).
- `orders`/`order_items` — snapshot name/price + nullable variant_id/deal_id + selections json.

## API surface (`/api/v1`)
- Public: `/site`, `/menu`, `/menu/items/{slug}`, `/deals`, `/deals/{slug}`, `/health`.
- Auth: `/auth/register|login|logout|me|forgot-password|reset-password` (forgot returns the token in debug mode — no mailer wired).
- Authenticated (sanctum): `/checkout` (re-prices server-side from DB), `/orders`, `/orders/{order}`.
- **No admin API** — the admin panel was removed; menu is managed in the RMS.

## Menu single source of truth (genz-admin → here)
- The menu source of truth is **`genz-admin-apis`** (`http://localhost:8002`), which exposes
  `GET /api/public/menu` (canonical menu.json shape **+ image URLs**).
- `App\Services\MenuImporter` upserts that feed **by slug** (stable IDs; deactivates missing
  items) and now also captures each item/category/deal **`image_url`** from the feed. Used by
  `MenuSeeder` (bootstrap from `database/data/menu.json`) and the `menu:sync` command.
- This app keeps a synced copy specifically so checkout can **re-price server-side** from a
  trusted source — even though display images/menu originate in genz-admin.
- `menu:sync` is **manual** (no scheduler). `ADMIN_MENU_URL` in `.env` points at the admin feed
  (default `http://localhost:8002/api/public/menu`; `RMS_MENU_URL` kept only as a cutover
  fallback). Workflow: edit menu/upload images in `genz-admin` → run `php artisan menu:sync` here.
- Image URLs flow through to `genz-web`/`genz-app` via the existing `image_url` fields on the
  `/menu` and `/deals` resources.

## Build status
- ✅ Built & verified: menu/deals API, auth, checkout/orders (server re-prices), RMS menu sync.
- ⏳ Pending: **online payment** — currently checkout sends `payment_method: cod|online`,
  but `online` has no gateway yet. Plan: a pluggable gateway abstraction + sandbox stub
  (gateway TBD: JazzCash/Easypaisa/Safepay, decided later).
- ⏳ Follow-up: forward online orders **into** the RMS (RMS `orders` has a `source` column).
