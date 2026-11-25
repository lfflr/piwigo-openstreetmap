# Aktuelle Übergabe für den gemeinsamen Entwicklungschat

**Stand:** 25.11.2026

**Entwicklungsrepository:** [lfflr/piwigo-openstreetmap](https://github.com/lfflr/piwigo-openstreetmap)

**Aktiver Branch:** [`rewrite/next-generation`](https://github.com/lfflr/piwigo-openstreetmap/tree/rewrite/next-generation) · [Draft-PR #8](https://github.com/lfflr/piwigo-openstreetmap/pull/8)

**Fork-Ursprung:** [Piwigo/piwigo-openstreetmap](https://github.com/Piwigo/piwigo-openstreetmap)

## Stand und Prüfnachweise

| Gegenstand | Verifizierter Stand |
| --- | --- |
| Ausgangsbranch `master` | `06e494f46429539a777fa3c4f24a812a9a6b8b84` |
| Überprüfter Entwicklungsstand vor diesem Doku-Abgleich | `45a5845aad60f06776c18ad46a92fd6d1b68af2b`. Den aktuellen Branch-HEAD zu Beginn jeder Runde frisch ermitteln. |
| Altplugin | `main.inc.php` führt `Version: auto`; `15.a` ist der oberste Changelog-Eintrag. Das Entwicklungsrepository hat keine Git-Tags. |
| R0 | Koordinatenvalidierung, Cache-Schlüssel und Nominatim-Antwortparser liegen unter `src/Geo/`. |
| R1 | `src/Geo/GeoWriteInput.php` validiert GPS-Schreibdaten. `include/ws_geo_write.inc.php` enthält den Adapter für `osm.geo.setCoordinates`. Die Registrierung in `main.inc.php` fehlt, der bestehende Schreib-Hook ist weiterhin aktiv. |
| Migration, Versionierung und Paket | Für den Neubau gibt es keine Migration, keine eigene Plugin-Version und kein freigegebenes Release oder Installations-ZIP. |
| GitHub Actions | [PHP-Syntaxprüfung für den oben genannten Entwicklungsstand](https://github.com/lfflr/piwigo-openstreetmap/actions/runs/45361947675) erfolgreich. Der Workflow führt `php -l` auf den versionierten PHP-Dateien aus. |
| Frühere lokale Testläufe | Am 24.11.2026 unter PHP CLI 8.4.24 dokumentiert. `GeoSmokeTest` erfolgreich, `GeoExtendedTest` 35 Assertions, `GeoWriteInputTest` 19 Assertions, `GeoApiAdapterTest` mit simuliertem Piwigo-Core und simulierter Datenbank erfolgreich. Diese Läufe verwendeten lokale Kopien ohne verifizierte Übereinstimmung mit dem Git-Checkout. |
| Prüfung am 25.11.2026 | Git-Checkout und Branch-Diff geprüft. Bootstrap, Plugin-Lebenszyklus und Legacy-Schreib-Hook sind gegenüber `master` unverändert. PHP-CLI fehlte in der Arbeitsumgebung, daher keine neue Ausführung der vier PHP-Tests. |
| Offene Abnahme | Die vier PHP-Tests aus einem echten Checkout ausführen. Piwigo-Core, MariaDB, Browser, Migration/Rollback, Piwigo-16/17-Kompatibilität und Live-Nominatim sind noch nicht nachgewiesen. |

## Offene Pull Requests

| PR | Thema | Rolle für die Fortsetzung |
| --- | --- | --- |
| [#2](https://github.com/lfflr/piwigo-openstreetmap/pull/2) | Orts-Presets absichern | Legacy-Korrektur, eigenes Review |
| [#3](https://github.com/lfflr/piwigo-openstreetmap/pull/3) | Reverse-Geocoding-Antworten pro Foto behandeln | Legacy-Korrektur, eigenes Review |
| [#4](https://github.com/lfflr/piwigo-openstreetmap/pull/4) | Regex in Leaflet Search | Separater Draft |
| [#5](https://github.com/lfflr/piwigo-openstreetmap/pull/5) | Einstellungsformular validieren | Legacy-Korrektur, eigenes Review |
| [#8](https://github.com/lfflr/piwigo-openstreetmap/pull/8) | Neuentwicklung R0/R1 | Aktiver Draft für die gemeinsame Weiterentwicklung |

## Nächster Arbeitsblock · R1

1. Aktuellen HEAD und Draft-PR #8 prüfen. Die vier PHP-Tests aus dem Checkout ausführen und PHP-Version, Befehle, Commit und Ergebnisse festhalten.
2. Auf einer isolierten Piwigo-16-Testinstanz mit MariaDB die Methodenregistrierung, Admin-/Sitzungsrechte, POST-/Tokenprüfung und Datenbankverhalten des Adapters prüfen. Den exakten Core-Tag und die PHP-/DB-Version festhalten. Piwigo 17 bei verfügbarem Teststand separat prüfen.
3. Die bestehenden Schreibpfade für Einzelbild und Batch erfassen. Nach bestandener Integration den neuen Adapter und die betroffenen Aufrufer gemeinsam umstellen. Lesekarten, bisherige Koordinaten und Bestandsdaten prüfen und einen Rückweg dokumentieren.
4. PR #8 bis zur Funktions- und Integrationsabnahme als Draft führen. Die Historie der Legacy-Befunde steht in [DEVELOPMENT-HANDOVER.md](DEVELOPMENT-HANDOVER.md). Die Umsetzungsschritte stehen in [REWRITE-PLAN.md](REWRITE-PLAN.md), Details zum Adapter in [R1-API-ADAPTER.md](R1-API-ADAPTER.md).

## Zusammenarbeit

- Vor Arbeitsbeginn im gemeinsamen Chat Arbeitspaket, verantwortliche Person, Branch und Ausgangs-SHA festhalten. Bei paralleler Arbeit getrennte Arbeitsbranches und klaren Dateizuschnitt vereinbaren.
- Schreibzugriff auf GitHub und Zugriff auf die Testinstanz pro Person klären. Zugangsdaten gehören außerhalb des Chats und Repositorys.
- Plugin-ID und Ordnername `piwigo-openstreetmap` beibehalten. Bestehende `osm_conf` und `osm_places` bei Updates erhalten. Die Karten dürfen für Gäste keine Änderungen an Alben oder Bildern auslösen.
- Nach jedem abgeschlossenen Paket diese Übergabe und den [Entwicklungsplan](REWRITE-PLAN.md) gemeinsam mit der Änderung aktualisieren. Ausgangs-/End-SHA, betroffene Dateien, Migrationen, ausgeführte Tests, offene Punkte und PR-/Paketlinks festhalten. Für den Wechsel in einen neuen Chat gibt es [CHAT-START.md](CHAT-START.md).
