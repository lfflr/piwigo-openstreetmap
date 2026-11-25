# Next-generation rewrite – work in progress

**Repository:** https://github.com/lfflr/piwigo-openstreetmap  
**Branch:** `rewrite/next-generation`  
**Status (2026-11-25):** R0 foundation and dormant R1 adapter in [draft PR #8](https://github.com/lfflr/piwigo-openstreetmap/pull/8). Integration and release acceptance are pending.

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

| Phase | State | Next acceptance step |
| --- | --- | --- |
| R0 | Geo foundation implemented in isolation | Rerun the domain tests from a Git checkout. |
| R1 | Input validation and dormant GPS-write adapter implemented | Verify against the selected Piwigo Core and MariaDB. Integrate the new write path and its callers after acceptance. |
| R2 | Pending | Photo, album and world-map parity, Leaflet refresh and browser tests. |
| R3 | Pending | GPX, saved places and secure batch GPS changes. |
| R4 | Pending | Reverse-geocoding jobs, cache, progress, rate limiting, simulation and overwrite behavior. |
| R5 | Pending | Piwigo 16 and 17 acceptance, large galleries, updates, rollback, translations and ZIP packaging. |

## Test commands

Run from the active development checkout and record the commit and PHP version.

```bash
git rev-parse HEAD
php --version
git ls-files -z -- '*.php' | xargs -0 -r -n 1 php -l
php tests/GeoSmokeTest.php
php tests/GeoExtendedTest.php
php tests/GeoWriteInputTest.php
php tests/GeoApiAdapterTest.php
```

Current evidence and outstanding integration checks are recorded in [HANDOVER-CURRENT.md](HANDOVER-CURRENT.md). The R1 design and test boundaries are described in [R1-API-ADAPTER.md](R1-API-ADAPTER.md) and [R1-SECURITY-CONTRACT.md](R1-SECURITY-CONTRACT.md).

## Old-code compatibility

The existing entrypoint remains unchanged on this branch while standalone domain modules are introduced. No new module is wired into Piwigo, so this branch is not deployable as a replacement. Do not merge until at least the functional and security parity tests pass.

## Documentation and handover workflow (mandatory)

- **Current state:** [HANDOVER-CURRENT.md](HANDOVER-CURRENT.md) – must include stable/development versions, branch and SHA, changed files, database migrations, executed test results with evidence, open defects, PR/release links, installable ZIPs, and exact next step.
- **Next-chat instructions:** [CHAT-START.md](CHAT-START.md) – update when the phase/working branch changes.
- **Documentation index:** [README.md](README.md) in this directory.
- **Legacy findings:** [DEVELOPMENT-HANDOVER.md](DEVELOPMENT-HANDOVER.md) – reference only; validate against current source before treating a finding as open or fixed.
- At the end of each phase, update the handover and phase statuses **in GitHub on the active development branch**, link actual test logs/commits, and identify the next phase. Never assert that tests passed without execution.
