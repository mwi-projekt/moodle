# Datenfluss: Dataform → Learning Agreement

## Übersicht

Das System liest automatisch Daten aus der **Anmeldung zum Auslandssemester** (gespeichert in `dataform_entries` und `dataform_contents`) und füllt sie im **Learning Agreement** ein.

## Architektur

```
Anmeldeformular (Dataform)
    ↓
dataform_entries (Einträge)
dataform_contents (Feldwerte)
    ↓
get_anmeldung_data.php (PHP-Handler)
    ↓
load_from_dataform.js (JavaScript)
    ↓
Learning Agreement Formular (ausgefüllt)
```

## Wie es funktioniert

### 1. Datenquelle: `dataform_entries` und `dataform_contents`

**`dataform_entries`** speichert jeden Anmeldungseintrag:
- `id`: Eindeutige Entry-ID
- `dataid`: Dataform-Aktivitäts-ID (z.B. 5 für "Anmeldung Auslandssemester")
- `userid`: Benutzer-ID
- `timecreated`: Zeitstempel
- etc.

**`dataform_contents`** speichert die Feldwerte:
- `fieldid`: Feld-ID (z.B. 1, 2, 3, ...)
- `entryid`: Verweis auf den Eintrag
- `content`: Der tatsächliche Feldwert
- `content1` bis `content4`: Für mehrteilige Inhalte

### 2. PHP-Handler: `get_anmeldung_data.php`

**Pfad:**
```
C:\Uni\6.Semester\Integrationsseminar\moodle\html_css\benutzeransicht\anmeldung\get_anmeldung_data.php
```

**Aufgabe:**
- Empfängt die `entryid` (und optional die `dataid`)
- Liest alle Felder einer Dataform aus
- Matched Feldnamen mit Learning Agreement Feldern
- Gibt JSON mit den Feldwerten zurück

**API:**
```
GET /anmeldung/get_anmeldung_data.php?entryid=123&dataid=5

Response:
{
  "success": true,
  "data": {
    "nachname": "Müller",
    "vorname": "Anna",
    "geburtsdatum": "1999-05-12",
    "studienrichtung": "Informatik",
    "studiengang": "Applied Computer Science",
    "kurs": "AI2024",
    "semester": "6. Semester",
    "gasthochschule": "University of Amsterdam"
  },
  "entryid": 123,
  "dataid": 5
}
```

### 3. JavaScript: `load_from_dataform.js`

**Pfad:**
```
C:\Uni\6.Semester\Integrationsseminar\moodle\html_css\benutzeransicht\learning agreement\load_from_dataform.js
```

**Aufgabe:**
- Wird beim Laden des Learning Agreement aufgerufen
- Lädt Daten entweder aus:
  1. `sessionStorage` (von der Anmeldung übergeben)
  2. PHP-Handler (direkter Dataform-Zugriff)
- Füllt die Formularfelder automatisch
- Zeigt Erfolgsmeldung

## Konfiguration: Field-IDs

Die wichtigste Konfiguration ist die **Zuordnung von Dataform-Feldnamen zu Learning Agreement Feldern**.

### Wo sind die Field-IDs definiert?

In der Datei **`get_anmeldung_data.php`** ca. ab Zeile 57:

```php
$fieldMapping = [
    'NACHNAME' => 'nachname',
    'VORNAME' => 'vorname',
    'GEBURTSDATUM' => 'geburtsdatum',
    'STUDIENRICHTUNG' => 'studienrichtung',
    'STUDIENGANG' => 'studiengang',
    'KURSNAME' => 'kurs',
    'AKTUELLES_SEMESTER' => 'semester',
    'ERSTWUNSCH' => 'gasthochschule',
];
```

### So findest du die korrekten Feldnamen

1. Moodle Admin-Panel öffnen
2. Gehe zu: **Kurse → Anmeldung Auslandssemester → Dataform-Aktivität**
3. Klicke auf **„Felder"**
4. Notiere die **exakten Feldnamen** (z.B. "NACHNAME", "VORNAME", etc.)
5. Aktualisiere die `$fieldMapping` in `get_anmeldung_data.php`

### Beispiel: Feldnamen-Anpassung

Wenn deine Felder anderen Namen haben, z.B.:
- Feld heißt "Name (Nachname)" statt "NACHNAME"
- Feld heißt "Gasthochschule (Erstwunsch)" statt "ERSTWUNSCH"

Dann musst du `get_anmeldung_data.php` so anpassen:

```php
$fieldMapping = [
    'NAME_NACHNAME' => 'nachname',        // Neuer Name
    'VORNAME' => 'vorname',
    'GEBURTSDATUM' => 'geburtsdatum',
    'STUDIENRICHTUNG' => 'studienrichtung',
    'STUDIENGANG' => 'studiengang',
    'KURSNAME' => 'kurs',
    'AKTUELLES_SEMESTER' => 'semester',
    'GASTHOCHSCHULE_ERSTWUNSCH' => 'gasthochschule',  // Neuer Name
];
```

## Learning Agreement Feldnamen

Die **rechte Seite** der Zuordnung (`nachname`, `vorname`, etc.) sind die Learning Agreement Feldnamen.

Diese entsprechen den HTML Attributen im Formular:

```html
<!-- Im HTML: data-field="nachname" -->
<div class="col-md-8">[[*NACHNAME]]</div>

<!-- Im JavaScript wird gesucht: querySelector('[data-field="nachname"]') -->
```

Die Learning Agreement Felder sind:
- `nachname`
- `vorname`
- `geburtsdatum`
- `studienrichtung`
- `studiengang`
- `kurs`
- `semester`
- `gasthochschule`

## Fehlerbehandlung

### Szenario 1: Keine Daten laden

Das System versucht **automatisch mehrere Fallbacks**:

1. ✅ sessionStorage (schnell, von Anmeldung)
2. ✅ get_anmeldung_data.php (direkt aus Dataform)

Wenn beide scheitern, zeigt sich keine Fehlermeldung — die Felder bleiben leer.

### Szenario 2: Nur manche Felder werden gefüllt

Überprüfe:
- Sind die Feldnamen korrekt geschrieben?
- Existiert überhaupt ein `dataform_contents` Eintrag für dieses Feld und diese Entry-ID?
- Ist der Feldtyp unterstützt? (text, select, date, checkbox, etc.)

### Debugging aktivieren

Öffne die **Browser-Konsole** (F12) und schaue nach:

```javascript
// Erfolg
Daten aus sessionStorage eingefüllt
// oder
Daten aus Dataform eingefüllt

// Fehler
Fehler beim Abrufen der Anmeldungsdaten: Request failed
```

## Unterstützte Feldtypen

Das System unterstützt standardmäßig diese Dataform-Feldtypen:

- ✅ **text** - normales Textfeld
- ✅ **textarea** - mehrzeiliger Text
- ✅ **select** - Dropdown
- ✅ **radiobutton** - Radio-Buttons
- ✅ **checkbox** - Häkchen (wird als 1/0 gespeichert)
- ✅ **number** - Zahlenfeld
- ✅ **date** - Datumsfeld
- ✅ **file** - Datei-Upload
- ✅ **url** - URL-Feld
- ✅ **selectmulti** - Multi-Select (mit Kommas verbunden)

Nicht unterstützte Feldtypen werden ignoriert:
- ❌ `_approve`, `_group`, `_user`, `_timecreated`, `_timemodified`
- ❌ `entryauthor`, `entrystate`

## Sicherheit

### Authentifizierung

Der `get_anmeldung_data.php` Handler prüft:
- ✅ Nutzer ist angemeldet
- ✅ Entry und Dataform existieren
- ✅ Nutzer hat Zugriff (grundsätzlich — kann erweitert werden)

### Zukünftige Verschärfungen

Falls erforderlich, könnte man folgende Sicherheitsmaßnahmen hinzufügen:

```php
// 1. Nur eigene Einträge auslesen lassen
if ($entry->userid !== $USER->id) {
    throw new Exception('Zugriff auf fremde Einträge verboten');
}

// 2. Berechtigungsprüfung gegen Dataform-Kontext
require_capability('mod/dataform:viewentry', $context);
```

## Debugging und Tipps

### JSON Response testen

```bash
curl "http://localhost/anmeldung/get_anmeldung_data.php?entryid=1&dataid=5"
```

### Field-IDs herausfinden (SQL)

```sql
SELECT id, dataid, name, type FROM mdl_dataform_fields WHERE dataid = 5;
```

Gibt dir alle Felder der Dataform mit ID 5.

### Inhalte einer Entry auslesen (SQL)

```sql
SELECT 
    f.name as fieldname,
    c.content
FROM mdl_dataform_contents c
JOIN mdl_dataform_fields f ON c.fieldid = f.id
WHERE c.entryid = 123;
```

Zeigt dir alle Feldwerte vom Eintrag 123.

## Zusammenfassung

| Komponente | Pfad | Aufgabe |
|---|---|---|
| **PHP Handler** | `get_anmeldung_data.php` | Liest Dataform-Daten | 
| **JavaScript** | `load_from_dataform.js` | Ladet und füllt aus |
| **Konfiguration** | `get_anmeldung_data.php` Zeile 57 | Field-Mapping |
| **HTML** | `learning_agreement_formular.html` | Script-Einbindung |

---

**Stand:** Mai 2026  
**Autor:** Automatisiertes System  
**Letzte Änderung:** Implementierung des Dataform-Auto-Fills

