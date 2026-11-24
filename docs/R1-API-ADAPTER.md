# R1 – Piwigo-GPS-Schreibadapter: Implementierung und Prüfstatus

**Stand:** 2026-11-24 · **Branch:** `rewrite/next-generation` · **PR:** [#8 (Draft)](https://github.com/lfflr/piwigo-openstreetmap/pull/8)

## Implementierung (noch nicht aktiviert)

- `include/ws_geo_write.inc.php` enthält `osm_register_geo_write_method($service)` für die Web-API-Methode `osm.geo.setCoordinates` und den Callback `osm_ws_geo_write($params, $service)`.
- Registrierung gemäß tatsächlich gelesenem Piwigo-Core `Piwigo/Piwigo`, `include/ws_core.inc.php`: `ws_add_methods`-Event mit `PwgServer::addMethod()`; Callback erhält Parameter und Server; Optionen `admin_only` / `post_only`; `WS_TYPE_ID`.
- Der Callback prüft zusätzlich `is_admin()`, schließt API-Key-Requests aus und erzwingt einen nichtleeren `pwg_token` im Abgleich mit `get_pwg_token()`.
- Danach erfolgt `GeoWriteInput::fromPayload()`, Bildexistenzprüfung und ein UPDATE mit ausschließlich validierter Ganzzahl-ID und fest formatierten Zahlen; bei Löschen werden beide GPS-Spalten zu `NULL`. `0/0` ist gültig und wird nicht als Löschung interpretiert.
- **Bewusste Sicherheitsgrenze:** Es wurde keine Registrierung von `osm_register_geo_write_method` in `main.inc.php` vorgenommen. Der neue Endpunkt ist deshalb im Plugin noch **nicht erreichbar**. Der weiterhin vorhandene Legacy-`ws_invoke_allowed`-Hook ist **nicht** beseitigt; die alte Sicherheitslücke bleibt bis zu einem späteren kontrollierten Umschalten bestehen.

## Ausgeführte lokale Prüfungen

**PHP CLI 8.4.24**, 2026-11-24. Testmaterial unter `/mnt/data/piwigo-r1`, aus den im Repository vorliegenden Quelltexten manuell als lokale Kopien hergestellt. Der Container kann `raw.githubusercontent.com` wegen DNS-Auflösungsfehlern nicht direkt abrufen. Die Identität der lokalen Kopie mit einem vollständigen Git-Checkout ist **nicht kryptographisch nachgewiesen**.

- `php -l` für drei Dateien in `src/Geo/`, `include/ws_geo_write.inc.php` sowie `tests/GeoApiAdapterTest.php`, `tests/GeoExtendedTest.php` und `tests/GeoWriteInputTest.php`: **7/7 ohne Syntaxfehler**.
- `php tests/GeoApiAdapterTest.php`: **OK** (Fake-Piwigo-Server und Fake-Datenbank, kein echter Core). Abgedeckt: Registrierungsoptionen, Gast-/API-Key-Ablehnung, fehlender/falscher Token, ungültige Koordinaten/IDs, Bild fehlt, Nullkoordinaten, Löschen zu `NULL`, simuliertes Datenbankversagen.
- `php tests/GeoExtendedTest.php`: **35 Assertions bestanden**.
- `php tests/GeoWriteInputTest.php`: **19 Assertions bestanden**.

Ein früherer erster Testlauf zeigte eine überflüssige PHP-`use`-Warnung und einen fehlenden `WS_TYPE_ID`-Stub in der auf GitHub abgelegten Testdatei. Beides wurde auf dem Entwicklungsbranch korrigiert; erneuter lokaler Lauf erfolgreich.

**Nicht ausgeführt:** Checkout-basierter GitHub-Test, Piwigo-16/17-Laufzeit-/Browserintegration, echte MariaDB, reale Auth-/CSRF-Negativtests, bestehende UI-Tests, Migrations- und Rollbacktests. Es gibt keinen installierbaren Neubau und keine Release-Freigabe.

## Geprüfte Referenzen

- [Piwigo-Core: Web-Service-Server](https://github.com/Piwigo/Piwigo/blob/master/include/ws_core.inc.php), darunter `PwgServer::run`, `addMethod`, `invoke` und `isAuthorizedMethodForAPIKEY`.
- [Piwigo-Core: Web-Service-Methodenregistrierung](https://github.com/Piwigo/Piwigo/blob/master/ws.php).
- [Piwigo-Core: Bilder-Web-API](https://github.com/Piwigo/Piwigo/blob/master/include/ws_functions/pwg.images.php), unter anderem `ws_images_setInfo`.
- [Piwigo Web API Dokumentation](https://github.com/Piwigo/Piwigo/wiki/Piwigo-Web-API): API-Key-Verhalten ab Piwigo 16 und `X-PIWIGO-API`-Header.

**Wichtig:** Das gelesene Core-`master` ist nicht automatisch identisch mit den Release-Tags 16.x und 17.x. Die Implementierung muss auf den konkret unterstützten Versionen getestet werden.

## Nächste Arbeit

1. Quelltexte aus echtem Git-Checkout prüfen und Tests erneut ausführen.
2. Piwigo-16- und Piwigo-17-Testinstanzen oder entsprechende Release-Tags verwenden; Methodenregistrierung, Tokenannahmen, Datenbankantworten und API-Key-Ausschluss überprüfen.
3. Bestehenden Legacy-Schreib-Hook nach erfolgreicher API-Integration entfernen (nicht vorher); Lesekarten dürfen dadurch nicht beeinträchtigt werden.
4. Für R1 Erweiterungen sicherer Persistenz und Transaktionen inklusive realer negativer Datenbanktests vorbereiten.
5. **PR #8 Draft belassen, nicht auf `master` mergen, keine ZIPs als RC veröffentlichen.**
