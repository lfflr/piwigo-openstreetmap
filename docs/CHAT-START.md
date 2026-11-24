# Startpunkt für einen neuen Entwicklungs-Chat

**Bitte zuerst lesen:** [HANDOVER-CURRENT.md](HANDOVER-CURRENT.md) , [REWRITE-PLAN.md](REWRITE-PLAN.md) und [PIWIGO-PLUGIN-STANDARDS.md](PIWIGO-PLUGIN-STANDARDS.md) sowie [R1-SECURITY-CONTRACT.md](R1-SECURITY-CONTRACT.md) im Branch `rewrite/next-generation` des Repositories https://github.com/lfflr/piwigo-openstreetmap. Die ältere [DEVELOPMENT-HANDOVER.md](DEVELOPMENT-HANDOVER.md) dokumentiert vor allem die statische Analyse des Legacy-Plugins.

## Kopierbare Aufgabenstellung

> Setze die Neuentwicklung von `lfflr/piwigo-openstreetmap` anhand der aktuellen GitHub-Dokumentation fort. Prüfe zuerst `docs/HANDOVER-CURRENT.md`, `docs/REWRITE-PLAN.md`, den aktuellen Entwicklungsbranch und Draft-PR #8. Arbeite nur in Entwicklungsbranches, sichere Bestandsdaten und erhalte die Plugin-ID `piwigo-openstreetmap`. Schreibe keine ungetesteten Funktionen als fertig, dokumentiere tatsächlich ausgeführte Tests mit Commit und Ergebnis. Nutze GitHub Actions sparsam; bevorzugt isolierte Testumgebung. Kein Merge auf `master`, keine Release-Veröffentlichung und keine Produktionsänderungen ohne dokumentierte Abnahme. Aktualisiere am Ende dieses Arbeitsschritts die beiden Übergabedokumente und gib Branch, SHA, PR-Link, Änderungen, Migrationen, Tests, offene Punkte, ZIPs und nächste Phase an.

## Was als gesichert gilt

- Entwicklungsrepository: `lfflr/piwigo-openstreetmap`; Fork-Ursprung `Piwigo/piwigo-openstreetmap`.
- Die Neuentwicklung wurde auf `rewrite/next-generation` begonnen, aber ist derzeit **nicht installierbar oder integriert**.
- Draft-PR: https://github.com/lfflr/piwigo-openstreetmap/pull/8
- Keine belegten erfolgreichen PHP- oder Browser-Tests des Neubaus; keine neuen Releases.
- Aktuellen Stand nicht aus dem Chat erraten: vor der Arbeit GitHub und Dokumentation lesen.

## Projektregeln

- Ein Phase/Arbeitspaket nach dem anderen; PR-Status, Commit-SHAs und Doku synchron halten.
- Altcode und Daten unangetastet lassen, bis Migration und Rückweg getestet sind.
- Nur funktions- und sicherheitsgeprüfte ZIPs als Installationspakete anbieten; oberster ZIP-Ordner `piwigo-openstreetmap/`.
- Offene Fragen ausdrücklich dokumentieren, nicht durch Vermutungen als erledigt markieren.

**R1-Fortsetzung:** Vor dem Aktivieren der neuen API unbedingt [R1-API-ADAPTER.md](R1-API-ADAPTER.md) und den dortigen Test-/Kompatibilitätsstatus prüfen. Der neue Handler ist absichtlich noch nicht in `main.inc.php` registriert; der Legacy-Hook darf nur kontrolliert nach Integrationstests ersetzt werden.
