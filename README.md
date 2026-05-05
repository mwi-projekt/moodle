# Prozesse des International Office

## Lokale Entwicklung

### Moodle lokal aufsetzen
TODO kurze Beschreibung und Links ergänzen zum lokalen Setup von Moodle

### Plugins lokal installieren
Um die eigenen Plugins in eine lokale Moodle Instanz zu integrieren, müssen sie zuerst in das Moodle Plugin Verzeichnis kopiert werden.
Um die Schritte nicht manuell vornehmen zu müssen, kann das Skript `copy-to-local-moodle.sh` verwendet werden.

```shell
sh copy-to-local-moodle.sh
```
Sofern die lokale Moodle Instanz nicht unter `../local-moodle` liegt, kann der gewünschte Pfad als Parameter angegeben werden.
```shell
sh copy-to-local-moodle.sh ../my-moodle-path
```

Sofern Änderungen nicht in diesem Repo, sondern direkt im lokalen Moodle-Verzeichnis gemacht wurden, können die Änderungen wie folgt wieder in das Repo übernommen werden:
```shell
sh sync-from-local-moodle.sh
```
Auch hier kann der gewünschte Pfad als Parameter angegeben werden.

Vor dem Commit sollten die Änderungen grundsätzlich, aber insbesondere nach dem `sync-from-local.sh` Skript genau überprüft werden.