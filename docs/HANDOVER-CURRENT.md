# Aktuelle Übergabe – piwigo-openstreetmap (Neuentwicklung)

**Stand:** 2026-11-24  
**Entwicklungsrepository:** https://github.com/lfflr/piwigo-openstreetmap  
**Ausgangsbranch:** `master`  
**Aktiver Entwicklungsbranch:** `rewrite/next-generation`  
**Entwicklungs-PR:** https://github.com/lfflr/piwigo-openstreetmap/pull/8 (Draft, offen; **nicht mergen**)  
**Fork-Ursprung:** https://github.com/Piwigo/piwigo-openstreetmap (historische Referenz, kein Pflicht-Diff)  
**Letzter bestätigter Commit auf dem Entwicklungsbranch bei Erstellung dieser Übergabe:** `637941e4ed5b729a67be5614688c7b7b40ffe65f` (vor dieser Dokumentation; aktuellen GitHub-HEAD stets neu prüfen).

> **Status: frühe Neuentwicklung, nicht vollständig, nicht installierbar, keine produktionsreife Ersatzversion.** Legacy-Plugin wurde im Entwicklungsbranch nicht verändert. Es wurden bisher keine PHP-Lint-, Unit-, Integrations-, Browser- oder Live-Nominatim-Tests der neuen Module erfolgreich ausgeführt oder dokumentiert. Nicht als getestet darstellen.

## 1. Projektziel

Das veraltete Piwigo-OpenStreetMap-Plugin technisch modern und sicher neu aufbauen, dabei Funktionsumfang und bestehende Installation erhalten: Foto-, Album- und Weltkarten, GPX, Orts-Presets, Batch-Geotagging, Reverse-Geocoding zu Tags, Konfiguration und Übersetzungen. Zielumgebung: Piwigo 16/17 und PHP 8; genaue Mindestversion und Kompatibilität noch zu validieren.

**Grundsätze:** Plugin-ID und Installationsordner `piwigo-openstreetmap` beibehalten; vorhandene `osm_conf` und `osm_places` durch kontrollierte, rückwärtsverträgliche Migrationen respektieren; Produktion und echte Daten nie ohne ausdrückliche Freigabe berühren. Kein Release oder Merge ohne Nachweis der Funktions- und Sicherheitsabnahme.

## 2. Tatsächlich ausgeführte Schritte

- GitHub-Repositories und Dokumentationskorrekturen: `lfflr/piwigo-openstreetmap` ist das Entwicklungsrepository. Das veraltete xbgmsharp-Repository verweist inzwischen auf `Piwigo/piwigo-openstreetmap`; die Dokumentation wurde bereits über PR #7 korrigiert.
- Im damaligen `master` wurden `main.inc.php`, `maintain.class.php`, `admin/admin_place.php`, `admin/admin_tag.php` und `include/ws_functions.inc.php` gelesen. Dabei sind konkrete Altcodeprobleme erkennbar: unsicher zusammengesetzte SQL-Statements, ein schreibender `ws_invoke_allowed`-Hook, synchroner Nominatim-Ablauf und fehleranfällige Antwortbehandlung.
- Branch `rewrite/next-generation` angelegt.
- Vier Dateien **neu** auf dem Entwicklungsbranch erstellt:
  - `src/Geo/Coordinates.php` – isolierte Koordinatenvalidierung und Cache-Key-Berechnung.
  - `src/Geo/AddressParser.php` – isolierte Nominatim-JSON-Auswertung mit Fallback city/town/village/hamlet/municipality.
  - `tests/GeoSmokeTest.php` – ausführbarer Testentwurf für Koordinaten und Parser.
  - `docs/REWRITE-PLAN.md` – Architektur, Migrationsprinzipien und Meilensteine.
- Draft-PR #8 eröffnet. Neue Module sind **nicht** in `main.inc.php` eingebunden; es gibt daher noch keine durch sie bereitgestellte Plugin-Funktion.

## 3. Versions- und Release-Status

| Gegenstand | Gesicherter Stand |
| --- | --- |
| Stabile Plugin-Version | Im früher analysierten Archiv ist `15.a` der letzte Changelog-Eintrag; aktuelle Version/Release auf GitHub **noch prüfen** |
| Aktuelle Entwicklungsversion | Noch **keine versionierte Neuentwicklung** |
| Plugin-Metadaten | `main.inc.php` im geprüften Altcode: `Version: auto` |
| Release / Installations-ZIP der Neuentwicklung | **Nicht vorhanden** |
| Migrationen für die Neuentwicklung | **Keine implementiert oder ausgeführt** |
| Änderungen an produktiven Dateien | **Keine** |
| Testausführung | **Keine belegten bestandenen Tests** |

## 4. Relevante bestätigte Code-Fundstellen

- `admin/admin_place.php`: POST-Eingaben werden direkt in SQL eingebettet.
- `include/ws_functions.inc.php`: Hook für `pwg.images.setInfo` führt Koordinatenupdates aus, obwohl der Hook nicht als eigenständige Schreib-API gedacht ist.
- `admin/admin_tag.php`: ungebündelte synchrone Reverse-Geocoding-Requests; unter anderem `$warnings`-Typfehler und mögliche Übernahme einer Antwort des vorherigen Fotos.
- `maintain.class.php`: Konfiguration und `osm_places` inklusive Install-/Update-/Uninstall-Logik. Bei Migrationen keine unbeabsichtigte Löschung zulassen.

Die vollständige frühere Archiv-Codeanalyse (mit weiteren, teilweise ungeprüften Befunden) steht in [DEVELOPMENT-HANDOVER.md](DEVELOPMENT-HANDOVER.md).

## 5. Architektur und Arbeitspakete

Siehe den fortlaufenden [REWRITE-PLAN.md](REWRITE-PLAN.md). Geplant:
1. R0: isolierte, testbare Geo-Domänenmodelle (begonnen, **nicht getestet**).
2. R1: sichere Persistenz, Piwigo-API und Authentifizierung/CSRF, Datenübernahme.
3. R2: Foto-, Album- und Weltkarten mit aktueller Leaflet-Version, browsergetestet.
4. R3: GPX, Orte, Batch-Koordinaten.
5. R4: Nominatim/Cache/Rate-Limit und fortsetzbarer, sichtbarer Tag-Sync.
6. R5: Integration, Performance, Migration, Piwigo-16/17-Abnahme, Release-ZIP.

## 6. Tests und Qualitätsnachweise

**Geplante, noch nicht nachweislich ausgeführte Befehle:**

```bash
php -l src/Geo/Coordinates.php
php -l src/Geo/AddressParser.php
php -l tests/GeoSmokeTest.php
php tests/GeoSmokeTest.php
```

Für jede weitere Phase tatsächlichen Testbefehl, Umgebung, Ergebnis, Datum, Commit und ggf. manuelle Abnahme notieren. Ohne Ausführung keinen „bestanden“-Status vergeben. GitHub Actions möglichst sparsam; lokale oder isolierte Dev-Instanz bevorzugt.

## 7. Bekannte Risiken / offene Entscheidungen

- Umfangreiche Funktionsparität und Migration sind noch nicht umgesetzt.
- Kein nachgewiesener Betrieb unter Piwigo 16 oder 17 und keine Browser-/Datenbanktests.
- CSRF-/Rechte-/XSS- und SQL-Angriffsflächen der bisherigen Plugin-Version sind nicht beseitigt.
- Nominatim-Richtlinie (Identifikation, Geschwindigkeit, Fehlerfälle und Cache) vor Live-Aufrufen gegen aktuelle Anforderungen prüfen.
- Verhalten von `Version: auto`, neue Plugin-Versionierung und Update-/Rollback-Prozess vor Testinstallation festlegen.
- Sicherheitsentscheidung für die bisherige anonyme Album-Erzeugung: im Neubau **keine Gästeschreiboperation**.
- Externe Bibliotheken/Lizenzen und Übersetzungen vor Release inventarisieren.

## 8. Nächster konkreter Entwicklungsauftrag

1. Aktuellen Branch/PR/Commitstand frisch von GitHub lesen.
2. Standalone-Geo-Module lokal mit PHP linten und testen; dokumentierte Testprobleme korrigieren.
3. Danach R1 umsetzen: sichere Piwigo-Web-API-Verträge und Persistenz-/Migrationskonzept inklusive negativer Tests.
4. PR #8 bis zur vollständigen Integrationsabnahme als **Draft** belassen. Nicht auf `master` mergen und kein Release erstellen.

## 9. Verbindliche Übergabekonvention für jede Phase

Zum Ende **jeder** Entwicklungsphase diese Datei aktualisieren mit: stabile Version, Entwicklungsversion, Repository/Branch/Commit-SHA, geänderten Funktionen/Dateien, Migrationen, **wirklich ausgeführten** Tests und Ergebnissen, bekannten Fehlern/offenen Fragen, PR-/Release-Links, vorhandenen Installations-ZIPs und nächster Phase. Alte Befunde dürfen nicht ungeprüft als aktuell ausgegeben werden. Zusätzlich [CHAT-START.md](CHAT-START.md) für den nächsten Chat aktuell halten.
