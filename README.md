# Prozesse des International Office

## Lokale Entwicklung

### Moodle lokal aufsetzen
Moodle Version 4.5 wird für die Entwicklung der Plugins verwendet.
Den lokalen Moodle Sever nach folgenden Moodle Dokumentationen aufsetzen:
- Windows: https://docs.moodle.org/502/de/Vollst%C3%A4ndiges_Installationspaket_f%C3%BCr_Windows#Installationsprozess
- Mac:https://docs.moodle.org/502/de/Installationspaket_f%C3%BCr_macOS

Der Server kann anschließend, wie in der Doku ausgeführt, über die 2 Dateien im Moodle Verzeichnis gestartet und gestoppt werden.

### Plugins lokal installieren
Um die eigenen Plugins in eine lokale Moodle Instanz zu integrieren, müssen sie zuerst in das Moodle Plugin Verzeichnis kopiert werden.

#### Erklärung
Es werden 3 Plugins in diesem Repo entwickelt und müssen auf dem lokalen Moodle Server installiert werden:
- dataform (dataform_plugin)
- dhbwio (io_plugin)
- zuweisungsmatrix

Diese Plugins müssen teilweise umbenannt werden und in verschiedene Verzeichnisse kopiert werden, damit sie von Moodle erkannt und installiert werden können.
- dataform_plugin -> dataform
- io_plugin -> dhbwio
- zuweisungsmatrix bereits richtig benannt

dataform und dhbwio müssen in das Verzeichnis `server/moodle/mod` kopiert werden. 
Die zuweisungsmatrix muss in das Verzeichnis `server/moodle/local` kopiert werden.

#### Automatisierung

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

### Moodle Raum importieren
Um den Moodle Raum unter 'moodle_raum_export' in den lokalen Moodle Server zu installieren folgende Schritte durchführen:

#### Mac:
1. Navigiere im Sever zu `Website-Administration -> Kurse -> Kurs wiederherstellen`
2. Lade dort die Datei aus dem Ordner `moodle_raum_export` aus diesem Repo hoch
3. Drücke auf Weiter und wähle auf der 2. Seite 'als neuen Kurs anlegen' aus
4. Drücke anschließend die nächsten Seiten einfach mit weiter durch, bis der Kurs importiert ist

#### Windows (viel komplizierter):
1. Suche in deinem lokalen Moodle Verzeichnis nach dem Ordner `server/php`
2. Dort sollte eine Datei `php.ini` liegen, öffne diese mit einem Texteditor
3. Suche in der Datei nach `upload_max_filesize` (ACHTUNG gibt es 2 mal) und `post_max_size`
4. Erhöhe alle 3 Werte auf mindestens 100M und **speichere die Datei**
5. Starte den lokalen Moodle Server neu
6. Navigiere im Sever zu `Website-Administration -> Kurse -> Sicherung -> Asynchrones Sichern / Wiederherstellen
7. Deaktiviere dort Asynchrone Sicherungen (Standardmäßig aktiviert) und speichere die Einstellungen
8. Navigiere nun zu `Website-Administration -> Kurse -> Kurs wiederherstellen`
9. Befolge die Schritte 2-4 aus der Mac Anleitung