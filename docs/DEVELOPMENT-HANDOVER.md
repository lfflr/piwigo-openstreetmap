# piwigo-openstreetmap – Entwicklungsübergabe

**Dokumentationsstand:** 2026-11-24  
**Entwicklungsrepository:** https://github.com/lfflr/piwigo-openstreetmap  
**Standardbranch laut GitHub-Abfrage:** `master`  
**Upstream (nur Referenz):** https://github.com/xbgmsharp/piwigo-openstreetmap  
**Quellgrundlage:** vom Nutzer bereitgestellte „Piwigo HANDOVER.md“, Analyse vom 2026-11-22 des Archivs `piwigo-osmtags-master.zip`. 

> Die folgenden Codebefunde stammen aus einer statischen Analyse des **Archivs**, nicht aus einer erneut durchgeführten vollständigen Prüfung des GitHub-Repositories. Es wurden dabei weder PHP-Lint noch Laufzeit-, Integrations- oder Nominatim-Tests ausgeführt. **Der konkrete Diff zwischen Archiv, Entwicklungsrepository und Upstream ist weiterhin offen.** Keine der beschriebenen Fehlerbehebungen gilt durch diese Dokumentation als implementiert.

## 1. Ausgangslage

- Plugin: OpenStreetMap für Piwigo; Karten auf Foto-/Albumseiten, vier Weltkartenvarianten, GPX, Batch-Geotagging, Orts-Presets und Reverse-Geocoding zu Tags (mit `tag_groups`).
- Im untersuchten Archiv: Changelog bis `15.a`, `Version: auto` in `main.inc.php`, Leaflet 0.7.7, Markercluster, Elevation 0.0.2, Search, Fontello, Übersetzungen.
- Der Archiv-Ordner `piwigo-osmtags-master` muss bei Installation `piwigo-openstreetmap` heißen.
- Angestrebte Umgebung Piwigo 16/17 und PHP 8; **konkrete Mindestversion und Kompatibilität noch nicht verifiziert**.
- GitHub-Änderungen, Branches, PRs und Releases gehören nach `lfflr/piwigo-openstreetmap`; Upstream wird nur verglichen.

## 2. Architektur im untersuchten Archiv

| Datei/Verzeichnis | Funktion |
| --- | --- |
| `main.inc.php` | Plugin-Einstieg, Konfiguration `osm_conf`, Events/Hooks |
| `maintain.class.php` | Installation, Update, Deinstallation, `osm_places` |
| `picture.inc.php`, `category.inc.php`, `menu.inc.php`, `gpx.inc.php` | Frontend und GPX |
| `osmmap.php`, `osmmap2.php` bis `osmmap4.php` | Weltkartenvarianten |
| `include/functions_map.php` | Kartenlogik und JS-Ausgabe |
| `include/ws_functions.inc.php` | Hook zu `pwg.images.setInfo` |
| `admin/admin_config.php` | Einstellungen |
| `admin/admin_tag.php` | Reverse-Geocoding/Tag-Sync |
| `admin/admin_place.php` | Orts-Presets |
| `admin/admin_photo.php`, `admin/admin_batchmanager.php` | Foto-/Batch-Administration |
| `leaflet/`, `template/`, `language/` | Kartenbibliotheken, Vorlagen, Sprachen |

## 3. Sicherheitsbefunde (nur statische Analyse)

| ID | Einschätzung | Fund / notwendige Maßnahme |
| --- | --- | --- |
| S1 | hoch | SQL-Injection-Risiko in `admin/admin_place.php` durch unescaped Werte; Escaping/Typprüfung |
| S2 | hoch | Schreibende Admin-Formulare und Album-Erzeugung ohne wirksamen CSRF-Schutz; `pwg_token` prüfen |
| S3 | mittel–hoch | Gastaufruf mit Karten-Bounds kann virtuelle Alben löschen/anlegen; Funktion entfernen oder streng autorisieren |
| S4 | mittel | `ws_invoke_allowed` verändert bereits Koordinaten bei `pwg.images.setInfo`; eigene abgesicherte Web-API-Methode statt Schreib-Hook |
| S5 | mittel, kontextabhängig | Potenzielles XSS in eingebettetem Karten-JS und Popup-HTML; JSON-HEX-Flags und korrektes Escaping |
| S6 | mittel | `check_input_parameter` erhält Werte statt Parameternamen, teils ungültige Regex; Validierung/Whitelists korrigieren |
| S7 | niedrig | Ungeprüfte Sprache in Nominatim-URL; Whitelist und URL-Encoding |

Diese Schweregrade sind Einschätzungen aus Quelltextlektüre, keine bestätigten Exploits.

## 4. Funktionale Befunde

**Automatisches Taggen:** Wiederverwendung einer alten Nominatim-Antwort bei Fehlern; `$warnings` kann String statt Array sein und unter PHP 8 Fehler auslösen; Rate-Limit-/HTTP-Fehler werden nicht sauber behandelt; `town`, `village`, `hamlet` fehlen; Anfrageabstand, eindeutiger User-Agent, Timeout und Cache fehlen; `overwrite` ist nicht umgesetzt; doppeltes Escaping kann Ortsnamen verfälschen; Simulation zählt irreführend Datenbankupdates.

**Einstellungen:** Tag-Sync-Optionen werden nicht gespeichert; Simulation und Standardsprache bleiben im Template vorbelegt; Hauptkonfiguration kann bei leeren/fehlenden POST-Feldern `null` speichern oder zurücksetzen; Koordinatenprüfung beanstandet Grenzwerte und speichert dennoch; `batch_unit_height` greift wegen fehlendem `global $conf` nicht; „--“ bei Saved Places setzt 0/0 statt Werte zu leeren.

**Performance:** Gesamter Tag-Sync läuft als ein synchroner POST und kann bei größeren Fotobeständen abbrechen; kein Fortschritt oder Fortsetzen. Weltkarten erzeugen alle geotaggten Einträge als JSON auf einmal.

**Weitere Qualitätsmängel:** Doppelte Prüfung `latitude != 0` statt latitude/longitude; fehlerhaftes `</options>`; tote Funktions-/KML-Verweise; fehlende `osm_`-Präfixe; keine Lazy-Loading-Hooks; veraltetes Leaflet und Tile-URLs; doppelte Anlage einer MyISAM-Tabelle; vierfache Kartenimplementierung; Stilregeln/Admin-Savebar nicht modernisiert.

**Bereits in der ursprünglichen Analyse korrigierte Fehlalarme:** Serialisierung von `osm_conf` ist plausibel; `image_id` und Bounding-Parameter sind bereits geprüft; spezifische bekannte Smarty-5-Antipatterns wurden nicht gefunden. Vollständige Smarty-5-Prüfung fehlt.

## 5. Geplanter Entwicklungsablauf (auf diesem Repository)

### A – `fix/security-admin`

S1, S2, S3 (nach Entscheidung), S5, S6 und S7 beheben. Bei der API-Schreiboperation S4 ist eine spätere Ablösung in Block C vorgesehen, Risiken bis dahin prüfen. Abnahme: `php -l`, gezielte negative CSRF-/SQL-/XSS-/Gast-Schreibtests auf isolierter Dev-Instanz.

### B – `fix/settings-persist`

Tag-Sync-Optionen persistent speichern; Formular aus Konfiguration rendern; Konfigurations-Merge und Fehlervalidierung korrigieren; `global $conf` ergänzen; „--“ darf keine Null-Island-Koordinaten schreiben. Abnahme: Persistenz nach Neuladen und Plugin-Update.

### C – `feat/tag-sync-chunked`

`ws_invoke_allowed`-Schreib-Hook durch registrierte, autorisierte, POST-/Token-geschützte Methode `osm.tags.syncChunk` ersetzen. Verarbeitung nach Bild-ID-Cursor und Limit (z. B. 10), mit `done`, `total`, `next_cursor`, `errors`, `warnings`. Cache-Tabelle `osm_geocode_cache`, konfigurierte Kontaktkennung, Rate-Limit für echte Requests, HTTP-Status-/Timeout-Behandlung; Ort-Fallback `city → town → village → hamlet → municipality`. Fortschrittsanzeige mit Abbruch/Fortsetzung, korrektes `overwrite`, eindeutige Simulationszählung. Abnahmeziel: 100 Testfotos ohne Request-Timeout; Zweitlauf überwiegend aus Cache.

### D – `chore/cleanup`

Qualitätsfehler Q1–Q8 beheben, ungenutzte Referenzen und Duplikate bereinigen; Leaflet-Modernisierung separat und mit Browserregression. Stilregeln und neue Admin-Savebar optional.

## 6. Dev-Testplan

Auf einer isolierten Piwigo-Testinstallation mit 20–30 GPS-Testfotos (darunter gleiche Orte, Dorf, Apostroph und Foto ohne GPS):

1. Vorherige Installation/Orte/Einstellungen sichern, Update installieren und Erhalt von Einstellungen sowie neuer Cache-Tabelle prüfen.
2. Tag-Sync simulieren/echt ausführen, unterbrechen/fortsetzen, Cache-Hits und unterschiedliche Ortsformate kontrollieren.
3. Batch-Geotagging einschließlich Entfernen der Koordinaten prüfen.
4. Admin-Schreibzugriffe ohne Token ablehnen; Gast darf keine Alben verändern; XSS-Testtitel darf nicht ausgeführt werden; ungültige Web-API-Token dürfen keine Koordinaten ändern.
5. Laufzeit und Browseransicht auf Piwigo 16/17 getrennt dokumentieren.

**Teststatus:** Für diese dokumentierten Ziele liegt in der Übergabe kein Ausführungsnachweis vor.

## 7. Release, Installation und Rollback

- Entwicklungsrepository: `https://github.com/lfflr/piwigo-openstreetmap.git`, Ausgangsbranch `master`.
- Pro Block separater Entwicklungsbranch mit PR in `master`; Readme und Changelog aktualisieren. Keine automatischen CI-/Produktionsdeployments vorausgesetzt.
- Erst nach Tests eine explizite Plugin-Version in `main.inc.php` prüfen und einen Release-Tag erstellen. `Version: auto` und der dazugehörige Update-Mechanismus müssen noch verifiziert werden.
- ZIP aus einem freigegebenen Git-Tag mit genau einem obersten Ordner `piwigo-openstreetmap/`:

```bash
git archive --format=zip --prefix=piwigo-openstreetmap/ -o ../piwigo-openstreetmap-VERSION.zip TAG
```

- Vor manueller Installation sichern: bisheriger Plugin-Ordner, `osm_places`, `osm_conf`. Keine Sicherungskopie im Piwigo-`plugins/`-Ordner belassen. Nach Upload Plugin-Administration und Kartenseiten testen.
- Rollback: Plugin deaktivieren, vorigen Ordner zurückspielen, bei Bedarf Konfiguration aus Sicherung wiederherstellen. Datenbankschemaänderungen müssen vorab einzeln auf Rückwärtsverträglichkeit geprüft werden.

## 8. Lokale Prüfung

Vorgesehene Umgebung laut ursprünglicher Übergabe: WSL2/Ubuntu, Docker Desktop/Compose, PHP 8.5, Piwigo-Testcontainer und MariaDB. Das ist ein Plan, kein Nachweis einer laufenden Umgebung. Syntaxprüfung z. B.:

```bash
find . -name '*.php' -not -path './language/*' -print0 | xargs -0 -n1 php -l
```

## 9. Offene Entscheidungen und Nachweise

- **O1:** Diff des untersuchten Archivs gegen `lfflr/piwigo-openstreetmap` und Upstream mit konkreten Commits erstellen. Noch keine geprüfte Fork-Differenz.
- **O2:** Unterstützte Piwigo-/PHP-Mindestversion festlegen; Smarty 5 vollständig prüfen.
- **O3:** Öffentliche virtuelle Album-Erzeugung entfernen oder ausschließlich für authentifizierte Admins implementieren.
- **O4:** Privat entwickeln oder zusätzlich auf piwigo.org veröffentlichen?
- **O5:** `mass_updates`-Escaping, `Version: auto`, Nominatim-Richtlinie und API-Key-Verhalten validieren.
- **O6:** Aktuelle Commit-SHAs, aktive Entwicklungsbranches, Releases, installierbare ZIPs und testbare Änderungen im Zielrepository vor Entwicklungsbeginn gesondert inventarisieren.

## 10. Entwicklerquellen

- [Piwigo Entwickler-Wiki](https://github.com/Piwigo/Piwigo/wiki)
- [Plugin-Entwicklung](https://github.com/Piwigo/Piwigo/wiki/Plugin-developpement)
- [Security and coding](https://github.com/Piwigo/Piwigo/wiki/Security-and-coding)
- [Piwigo Web API](https://github.com/Piwigo/Piwigo/wiki/Piwigo-Web-API)
- [Piwigo 16 – technische Änderungen](https://github.com/Piwigo/Piwigo/wiki/Technical-changes-in-Piwigo-16)
- [Piwigo 17 – technische Änderungen](https://github.com/Piwigo/Piwigo/wiki/Technical-changes-in-Piwigo-17)
- [Originalprojekt](https://github.com/xbgmsharp/piwigo-openstreetmap)

## 11. Nächster Schritt

1. Quellvergleich (Archiv / eigener `master` / Upstream), vorhandene Änderungen dokumentieren.
2. Sicherheit und Entscheidung O3 priorisieren.
3. Entwicklungsblock A als eigenen PR implementieren, Tests protokollieren, erst danach B/C/D.
