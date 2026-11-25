# Piwigo-Plugin-Standards für OpenStreetMap

**Stand:** 2026-11-24 · **Status:** Entwicklerreferenz, kein Testnachweis  
**Entwicklungsrepository:** https://github.com/lfflr/piwigo-openstreetmap  
**Fork-Ursprung:** https://github.com/Piwigo/piwigo-openstreetmap  
**Zielplattform:** Piwigo 16, perspektivisch 17; PHP-Mindestversion und tatsächliche Kompatibilität noch festzulegen.

Diese Kurzreferenz trennt **Piwigo-Konventionen [D]** von **unseren Architekturentscheidungen [E]**. Vor dem ersten Merge API-Signaturen am installierten Core validieren.

## 1. Einstieg und Plugin-Lebenszyklus

- **[D]** Verzeichnisname ist Plugin-ID: `piwigo-openstreetmap`. Metadaten und Bootstrap in `main.inc.php`; dort nur notwendige Initialisierung und Eventregistrierung.
- **[D]** Event-Handler mit `add_event_handler()`, bei Bedarf die Variante mit Dateipfad für Lazy-Loading. Zeitpunkt und Argumente eines Events im passenden Piwigo-Core prüfen; nicht von später verfügbaren Template-/User-/Sprachkontexten während des frühen Bootstrap ausgehen.
- **[D]** `maintain.class.php` mit `piwigo_openstreetmap_maintain extends PluginMaintain` und `install`, `activate`, `update`, `deactivate`, `uninstall`.
- **[E]** Plugin-ID und bestehende Konfiguration `osm_conf` sowie Tabelle `osm_places` erhalten. Datenmigrationen idempotent und rückwärtsverträglich. Deaktivieren darf keine Daten löschen; Uninstall nur nach dokumentiertem Verhalten.
- **[D/E]** Globale Funktionsnamen eindeutig präfixen (`osm_`); neue Logik in isolierten Klassen mit kontrolliertem Laden kapseln. Fehler kontrolliert behandeln.

## 2. Daten, Schreibzugriffe, API und Sicherheit

- **[D]** Eigene Web-API-Methoden über `ws_add_methods` registrieren und Methodenverträge in der verwendeten Piwigo-Version kontrollieren. `ws_invoke_allowed` ist kein Schreib-API-Ersatz.
- **[D]** Eingaben über validierte Parameter verarbeiten; `check_input_parameter` erhält einen Parameternamen, nicht den Wert. IDs als gültige Ganzzahlen, Koordinaten als endliche Zahlen in den Bereichen −90..90 / −180..180 prüfen.
- **[D]** Mutierende Browserformulare mit `pwg_token` und serverseitiger `check_pwg_token()`-Prüfung; Berechtigung zusätzlich prüfen. API-Token/Key-Authentifizierung und Browser-CSRF nicht gleichsetzen.
- **[D]** API-Key-Unterstützung in Piwigo 16 berücksichtigen; Details (z. B. Header und verbotene Methoden) anhand der **genauen Zielversion** prüfen, bevor sie angeboten wird.
- **[E]** Schreibende Plugin-Endpunkte zunächst nur für authentifizierte Administratoren; POST, Rollenprüfung, Tokenprüfung, Eingabevalidierung und gezielte negative Tests. **Gäste dürfen niemals Alben/Fotos verändern.**
- **[D/E]** SQL konsequent mit Piwigos Datenbankhilfen und korrekt escaped/validierten Werten; kein POST-String in `IN (...)` oder `INSERT`. HTML-, Attribut-, JSON- und JavaScript-Kontexte separat escapen. Popups bevorzugt mit `textContent` aufbauen.

## 3. Oberfläche und Piwigo 16/17

- **[D]** Piwigo 16 verwendet Smarty 5. Templates gegen die Smarty-5-Migrationshinweise prüfen (nicht nur auf einen einzelnen Syntaxfall testen).
- **[D]** Für Admin-Einstellungen Piwigos aktuelle Verwaltungsoberfläche einschließlich Speichern-/Erfolgsmeldungsmechanismus prüfen.
- **[E]** Keine neue schwere Frontend-Laufzeit. Aktuelles, fest versioniertes Leaflet mit getesteten Plugins (Cluster, GPX/Elevation nur sofern benötigt), responsive Karten, tastaturbedienbare Controls und saubere Theme-Kompatibilität.
- **[E]** Kartendaten nach Bedarf laden und begrenzen; nicht alle Fotos einer großen Galerie ungefiltert in ein einziges Inline-JSON packen. Nur Bilder bereitstellen, die der aktuelle Benutzer sehen darf.

## 4. Nominatim und Hintergrundverarbeitung

- **[E]** Reverse-Geocoding von reiner Datenverarbeitung trennen (Provider, Rate-Limiter, Cache, Tag-Zuordnung, Piwigo-Integration).
- **[E]** Externe Anfragen transparent und optional; GPS-Koordinaten verlassen dabei die Installation. Identifizierbarer konfigurierbarer User-Agent/Kontakt, Cache, Timeouts, Fehlerstatus, Retry/Backoff, faire Ratenbegrenzung.
- **[E]** Keine monolithische Synchronisation über alle Bilder. Kleine fortsetzbare Pakete per Cursor, kontrollierter Abbruch, Simulation, Fortschrittszahlen und `overwrite`-Semantik.
- **[D/E]** Aktuelle Nominatim-Nutzungsrichtlinie **vor** Live-Tests/Produktivbetrieb prüfen; nicht von früheren 2015er Annahmen ausgehen.

## 5. Projektstruktur (Zielbild, noch nicht vollständig implementiert)

```text
piwigo-openstreetmap/
  main.inc.php
  maintain.class.php
  admin/                    # Piwigo Admin-Einbindung
  src/Geo/                 # Koordinaten, Adressen, Tag-Abbildung
  src/Service/             # Geocoding, Rate-Limit, Batch-Jobs
  src/Repository/          # Piwigo-Datenzugriffe, Cache
  include/                 # Piwigo-Hooks/Adapter
  template/                # Smarty-Templates
  leaflet/                 # versionierte Browser-Abhängigkeiten
  language/                # Piwigo-Übersetzungen
  tests/                   # Unit-, Security-, Integrationstests
  docs/
```

**Erhalten:** Altcode nicht ohne Migrations-/Funktionsabnahme entfernen. Die neue Architektur schrittweise an die Plugin-Eintrittspunkte anbinden.

## 6. Verbindliche Prüfungen vor Merge und Release

1. PHP-Lint + Tests der isolierten Module mit dokumentierten Befehlen und Ergebnissen.
2. Rechte/CSRF/SQL/XSS/IDOR und API-Verhalten einschließlich ungültiger Eingaben auf isolierter Dev-Instanz.
3. Installations-/Update-/Rollback-Tests mit vorhandenen `osm_conf`/`osm_places`.
4. Funktionsparität: Foto-, Album-, Weltkarten, GPX, Batch-Koordinaten, Orte, Reverse-Tags, Übersetzungen.
5. Piwigo 16 und 17 (soweit verfügbar) sowie Smarty 5, mobile Ansicht, große Galerien und Browser-Tests.
6. Release-ZIP nur nach Nachweis; oberster Ordner `piwigo-openstreetmap/`, keine produktiven Daten.

## 7. Quellen und Verifikationspunkte

- [Piwigo-Entwickler-Wiki](https://github.com/Piwigo/Piwigo/wiki)
- [Plugin développement](https://github.com/Piwigo/Piwigo/wiki/Plugin-developpement)
- [Security and coding](https://github.com/Piwigo/Piwigo/wiki/Security-and-coding)
- [Piwigo Web API](https://github.com/Piwigo/Piwigo/wiki/Piwigo-Web-API)
- [Coding guidelines](https://github.com/Piwigo/Piwigo/wiki/Coding-guidelines)
- [Technical changes in Piwigo 16](https://github.com/Piwigo/Piwigo/wiki/Technical-changes-in-Piwigo-16)
- [Technical changes in Piwigo 17](https://github.com/Piwigo/Piwigo/wiki/Technical-changes-in-Piwigo-17)
- [Smarty 5 upgrading guide](https://smarty-php.github.io/smarty/stable/upgrading/)
- [Nominatim Usage Policy](https://operations.osmfoundation.org/policies/nominatim/)

Diese Links dokumentieren Normen/Prüfpunkte, nicht die Durchführung einer vollständigen Kompatibilitätsabnahme. Für neue Chats immer auch [HANDOVER-CURRENT.md](HANDOVER-CURRENT.md), [REWRITE-PLAN.md](REWRITE-PLAN.md) und [CHAT-START.md](CHAT-START.md) lesen.
