# R1 – Schreiboperationen und Sicherheitsvertrag

**Stand:** 2026-11-24 · **Status:** Entwurf und erste validierte Domänengrenze, **keine produktive API**

## Implementiert auf `rewrite/next-generation`

- `src/Geo/Coordinates.php`: endliche Koordinaten, gültiger Wertebereich, 0/0 zulässig.
- `src/Geo/GeoWriteInput.php`: strikt validierter Schreibbefehl mit positiver dezimaler Bild-ID, explizitem `operation=set|delete`, beiden Pflichtkoordinaten für `set`, keiner Koordinate für `delete`, Zurückweisung unbekannter Parameter.
- `tests/GeoWriteInputTest.php`: gültige Nullkoordinaten, Löschbefehl und 14 negative Eingabe-/Grenzwertfälle.
- Noch keine Piwigo-Registrierung, kein Speichervorgang, keine Datenbankmigration.

## Verbindlicher Ablauf jeder künftigen Schreib-API

1. Methode im Piwigo-Web-API-Mechanismus der **konkret getesteten** Core-Version registrieren (Signatur am Core verifizieren).
2. Für Browser-Aufrufe Session und serverseitigen CSRF-Schutz überprüfen; bei API-Key-Nutzung Autorisierung gesondert überprüfen. Token-Prüfung darf nicht nur abhängig davon sein, ob ein Token mitgesendet wurde.
3. Zugriff ausschließlich für administrative Berechtigungen; keine Gäste- oder Community-Schreibrechte.
4. Antrag vollständig mit `GeoWriteInput::fromPayload()` validieren. Fehlende Felder, zusätzliche Felder, ungültige Werte, manipulierte IDs zurückweisen.
5. Existenz und Berechtigung für das Bild **vor** jeder Datenmutation prüfen.
6. Bei `set` beide geprüften Koordinaten schreiben; bei `delete` beide Datenbankfelder auf `NULL` setzen. Niemals einen leeren Wert fälschlich als 0/0 behandeln. Piwigo-DB-Escaping/Parameterisierung am Core prüfen.
7. Erfolg erst nach bestätigtem Datenbank-Update zurückgeben. Revisions-/Nebenläufigkeitsverhalten prüfen.
8. Negative Tests: ohne Auth, ohne/mit ungültigem Token, falsche Rolle, fremde/nicht existierende Bild-ID, ungültige Koordinaten, manipulierte Operation, wiederholte Requests. Nach jedem Fehler nachweisen, dass der Datensatz unverändert ist.

**Wichtig:** Der reine Validator implementiert Schritte 2, 3, 5, 6, 7 und 8 **nicht**. Es darf daraus kein Sicherheitsfreigabevermerk für das laufende Legacy-Plugin abgeleitet werden.

## Aktuelle Testbelege

2026-11-24 lokal mit PHP CLI 8.4.24, anhand der zuvor aus GitHub gelesenen bzw. im GitHub-Branch angelegten und für die isolierte Prüfung nachgebildeten Quelltexte:

- `php -l src/Geo/GeoWriteInput.php`: bestanden.
- `php -l tests/GeoWriteInputTest.php`: bestanden.
- `php tests/GeoWriteInputTest.php`: `OK: GeoWriteInputTest (19 assertions)`.
- `php tests/GeoExtendedTest.php`: `OK: GeoExtendedTest (35 assertions)`.
- Lint der beiden bereits vorhandenen Geo-Module und des erweiterten Tests: bestanden.

**Einschränkung:** Der Container konnte GitHub wegen DNS-Ausfall nicht direkt klonen; die isolierten Tests prüften lokal nachgebildete Quelltexte, nicht einen frisch ausgecheckten Commit. Vollständiger Checkout, identischer Dateivergleich und erneuter Test auf dem Originalbranch sind nachzuholen. Keine Piwigo-/MariaDB-/Browser-/Live-Nominatim-Tests.

## Nächster Schritt

Den API-Vertrag anhand des installierten Piwigo-16-Cores und separat Piwigo 17 prüfen, authentifizierten Adapter und sicher parametrisierte Persistenz in getrennten Testklassen implementieren, negative Tests auf isolierter Dev-Installation ausführen. Bis dahin PR #8 als Draft belassen.
