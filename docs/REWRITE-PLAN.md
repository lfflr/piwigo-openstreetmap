# Next-generation rewrite – work in progress

**Repository:** https://github.com/lfflr/piwigo-openstreetmap  
**Branch:** `rewrite/next-generation`  
**Status:** first isolated implementation only; **NOT an installable rewrite or release**.

## Scope and invariants

- Retain Piwigo plugin ID and install folder `piwigo-openstreetmap`.
- Retain existing `osm_conf` and `osm_places` through explicitly versioned, reversible migrations.
- Preserve photo, album, world maps, GPX, batch GPS, saved places, and reverse-geocoding tags.
- Keep write APIs admin-only and CSRF-protected, with full input validation.
- No public map visit may create or delete albums or modify photos.
- Do not send photo titles, descriptions, account data, or GPS to third parties except where specifically needed and configured. Nominatim reverse requests necessarily share GPS.
- Respect Nominatim service usage limits, cache identical requests, configure identifying User-Agent/contact, backoff for 429/503 and give users a way to disable outbound geocoding.
- Use responsive Leaflet map views, progressive loading/clustered display, and batch geocoding with cancellation/resumption.
- Avoid a dependency on a JS framework and preserve gettext/Piwigo translation integration.

## Evidence

The checked `master` files (before this branch) include `main.inc.php`, `maintain.class.php`, `admin/admin_place.php`, `admin/admin_tag.php`, and `include/ws_functions.inc.php`. The existing API hook writes coordinates while Piwigo is authorizing a different method; `admin_place.php` interpolates raw POST values into SQL; `admin_tag.php` uses a synchronous Nominatim loop with fragile error handling. These observations do not constitute runtime exploit tests.

## Module boundaries

1. **Geo domain**: validated coordinates, JSON response parsing, tag mapping, geometry and cache keys. No I/O; unit-testable.
2. **Persistence**: prepared/escaped Piwigo queries, DB prefix support, cache records, versioned migrations, transaction safeguards.
3. **Services**: reverse geocoding and rate limiting with injectable HTTP provider; bounded batches, stable cursor, resumable state.
4. **Piwigo integration**: registered `ws_add_methods` APIs with auth/CSRF, lifecycle handlers and configuration migrations.
5. **UI**: map components for photo/album/world/GPX, settings, batch progress, keyboard accessibility and mobile layouts.

## Delivery checkpoints

- R0 (started): typed coordinate and response parsing modules with executable standalone smoke test.
- R1: safe persistence and update strategy, security contracts and negative tests.
- R2: photo/album/world map parity, Leaflet refresh and frontend browser tests.
- R3: GPX, saved places, secure batch GPS changes.
- R4: API-based reverse-geocoding jobs, cache, progress, rate limiting, simulation and overwrite semantics.
- R5: end-to-end Piwigo 16 and 17 acceptance, large-gallery performance tests, update/rollback tests, translation and ZIP packaging.

## Tests

```bash
php -l src/Geo/Coordinates.php
php -l src/Geo/AddressParser.php
php -l tests/GeoSmokeTest.php
php tests/GeoSmokeTest.php
```

**Not yet tested:** Piwigo integration, database migrations, browsers, real Nominatim, GPX, photo/album/world maps, cross-version compatibility. No production deployment is authorized.

## Old-code compatibility

The existing entrypoint remains unchanged on this branch while standalone domain modules are introduced. No new module is wired into Piwigo, so this branch is not deployable as a replacement. Do not merge until at least the functional and security parity tests pass.
