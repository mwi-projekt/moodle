# DHBW International Office - Moodle Plugin

## Übersicht
Dieses Moodle-Plugin bietet eine Lösung für die Hochschulverwaltung des International Office. Es ermöglicht die Verwaltung von Partnerhochschulen, deren Visualisierung auf einer interaktiven Weltkarte und die automatische Generierung von Informationsseiten. 

*Hinweis: Die Bewerbungsfunktion wird derzeit durch ein separates dataform-Plugin abgedeckt.*

## Funktionen

### Partnerhochschulverwaltung
- CRUD-Oberfläche für die Verwaltung von Partnerhochschulen
- Detaillierte Informationen zu jeder Hochschule (Land, Stadt, Koordinaten, Beschreibung)
- Verwaltung von verfügbaren Plätzen und Semestern
- Aktivieren/Deaktivieren von Hochschulen

### Visualisierung und Informationsdarstellung
- Interaktive Weltkarte mit allen Partnerhochschulen
- Tooltips mit Basisinformationen
- Navigation von der Karte zu Detailseiten
- Gruppierung von Hochschulen nach Ländern in der Listenansicht
- KPI-Darstellung auf Übersichtsseiten

### Erfahrungsberichte
- Integration von studentischen Erfahrungsberichten mit Hochschulseiten
- Bewertungssystem für Austauscherfahrungen
- Durchsuchbare Berichtsdatenbank

### E-Mail-Kommunikation
- Konfigurierbare E-Mail Vorlagen
- Unterstützung für Vorlagevariablen

## Anforderungen
- Moodle 4.2 LTS oder höher
- Moodle Dataform 3.3.2 oder höher

## Installation
1. Laden Sie das Plugin von diesem Repository herunter
2. Extrahieren Sie den Ordner und benennen Sie ihn in `dhbwio` um
3. Verschieben Sie den Ordner in das Verzeichnis `mod` Ihrer Moodle-Installation
4. Melden Sie sich als Administrator an und navigieren Sie zu Site-Administration > Benachrichtigungen
5. Folgen Sie den Anweisungen auf dem Bildschirm, um die Installation abzuschließen
6. Das Plugin erstellt automatisch alle erforderlichen Datenbanktabellen

## Verwendung

### Hinzufügen des Moduls zu einem Kurs
1. Aktivieren Sie die Bearbeitung in Ihrem Kurs
2. Klicken Sie auf "Aktivität oder Material hinzufügen"
3. Wählen Sie "International Office" aus der Liste
4. Konfigurieren Sie die Moduleinstellungen und speichern Sie

### Konfigurationsoptionen
- **Weltkarte aktivieren**: Schaltet die interaktive Weltkartenfunktion ein/aus
- **Erfahrungsberichte aktivieren**: Schaltet die Erfahrungsberichtsfunktion ein/aus

### Benutzerrollen und Berechtigungen
Das Plugin enthält benutzerdefinierte Berechtigungen für verschiedene Benutzerrollen:

| Berechtigung | Beschreibung | Standardzuweisung |
|------------|-------------|-------------------|
| mod/dhbwio:view | International Office-Inhalte anzeigen | Alle Benutzer |
| mod/dhbwio:manageuniversities | Hochschuldaten verwalten | Trainer, Manager |
| mod/dhbwio:submitreport | Erfahrungsberichte einreichen | Studenten |
| mod/dhbwio:viewreports | Statistiken anzeigen | Trainer, Manager |

## Entwicklung
Dieses Plugin folgt den Moodle-Entwicklungsstandards und bewährten Praktiken:
- Der Code entspricht den Moodle-Stilrichtlinien
- Übersetzungsbereit durch Verwendung des Moodle-Sprachsystems
- Ordnungsgemäße Verwendung von Berechtigungen für die Berechtigungsverwaltung
- XMLDB für Datenbankdefinitionen

### Projektstruktur
Das Plugin folgt der Standardstruktur eines Moodle-Moduls:
```
mod_dhbwio/
├── backup/             # Backup/Restore-Funktionalität
├── classes/            # PHP-Klassen
│   ├── event/          # Ereignisklassen
│   ├── external/       # Externe Funktionen
│   ├── form/           # Formulardefinitionen
│   ├── output/         # Ausgabe-Renderer
│   └── privacy/        # Privacy API-Implementierung
├── db/                 # Datenbankdefinitionen
├── lang/               # Sprachzeichenketten
├── pix/                # Icons und Bilder
├── templates/          # Mustache-Vorlagen
├── amd/                # JavaScript-Module
├── lib.php             # Bibliotheksfunktionen
├── locallib.php        # Interne Modulfunktionen
├── mod_form.php        # Modulinstanzformular
├── index.php           # Modulindexseite
├── view.php            # Hauptansichtsseite
└── version.php         # Versionsinformationen
```

## Zukünftige Erweiterungen
- Tiefere Integration mit dem bestehenden dataform-Plugin für Bewerbungen
- Verbessertes Statistikmodul