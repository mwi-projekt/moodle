# Feldname-Referenz für deine Anmeldung

Basierend auf den Feldbezeichnungen in deinem **Anmeldeformular** hier die wahrscheinlichen Dataform-Feldnamen:

## Kapitel 1: Persönliche Informationen

| Anzeige im Formular | Wahrscheinlicher Feldname | Datentyp |
|---|---|---|
| Nachname | `NACHNAME` | text |
| Vorname | `VORNAME` | text |
| Geburtsdatum | `GEBURTSDATUM` | date |
| Nationalität | `NATIONALITAET` | select |
| Muttersprache | `MUTTERSPRACHE` | select |

## Kapitel 2: Kontaktdaten

| Anzeige im Formular | Wahrscheinlicher Feldname | Datentyp |
|---|---|---|
| E-Mail-Adresse der DHBW | `EMAIL` | text |

## Kapitel 3: Studium

| Anzeige im Formular | Wahrscheinlicher Feldname | Datentyp |
|---|---|---|
| Studiengang | `STUDIENGANG` | select |
| Studienrichtung | `STUDIENRICHTUNG` | select |
| Kurs | `KURSNAME` | text |
| Studiengangsleitung | `STUDIENGANGSLEITUNG` | text |
| Hat bereits eine Absprache mit der Studiengangsleitung stattgefunden? | `ABSPRACHE_MIT_STUDIENGANGSLEITUNG` | radiobutton |
| Semester zum Zeitpunkt der Anmeldung | `AKTUELLES_SEMESTER` | select |

## Kapitel 4: Partnerunternehmen

| Anzeige im Formular | Wahrscheinlicher Feldname | Datentyp |
|---|---|---|
| Name | `UNTERNEHMEN` | text |
| Ansprechperson | `ANSPRECHPERSON_UNTERNEHMEN` | text |
| E-Mail der Ansprechperson | `ANSPRECHPERSON_EMAIL` | text |
| Hat bereits eine Absprache mit dem Unternehmen stattgefunden? | `ABSPRACHE_MIT_UNTERNEHMEN` | radiobutton |

## Kapitel 5: Auslandssemester

| Anzeige im Formular | Wahrscheinlicher Feldname | Datentyp |
|---|---|---|
| Erstwunsch | `ERSTWUNSCH` | select |
| Zweitwunsch | `ZWEITWUNSCH` | select |
| Drittwunsch | `DRITTWUNSCH` | select |
| Zählen Sie sich in Bezug auf Ihre Bildungschancen... | `BENACHTEILIGUNG_BILDUNGSCHANCEN` | textarea |
| Ihre Nachricht an uns | `NACHRICHT` | textarea |
| Falls mein Auslandssemester realisiert werden kann... | `VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT` | checkbox |
| Einverständniserklärung Datenschutz | `EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ` | checkbox |

## Für das Learning Agreement benötigt

Die `get_anmeldung_data.php` nutzt aktuell diese Felder:

```php
$fieldMapping = [
    'NACHNAME' => 'nachname',                    // ✅ Spalte 1
    'VORNAME' => 'vorname',                      // ✅ Spalte 2
    'GEBURTSDATUM' => 'geburtsdatum',            // ❌ Nicht im LA (optional)
    'STUDIENRICHTUNG' => 'studienrichtung',      // ✅ Spalte 1
    'STUDIENGANG' => 'studiengang',              // ❌ Nicht vorhanden?
    'KURSNAME' => 'kurs',                        // ✅ Spalte 3 (Zeile 1)
    'AKTUELLES_SEMESTER' => 'semester',          // ✅ Spalte 2 (Zeile 2)
    'ERSTWUNSCH' => 'gasthochschule',           // ✅ Spalte 3 (Zeile 2)
];
```

## Überprüfen der tatsächlichen Feldnamen

Wenn du unsicher bist, ob die Feldnamen stimmen:

### Methode 1: Moodle Admin Interface

1. Gehe zu: **Kurs > Anmeldung Auslandssemester > Dataform**
2. Klicke oben auf **„Felder"**
3. Die Spalte **„Name"** zeigt die exakten Feldnamen

### Methode 2: Datenbank-Query

```sql
SELECT id, name, type 
FROM mdl_dataform_fields 
WHERE dataid = 5  -- 5 = deine Dataform-ID (anpassen!)
ORDER BY name;
```

### Methode 3: PHP Debug

Füge temporär in `get_anmeldung_data.php` ein:

```php
// Nach: $fields = $DB->get_records('dataform_fields', ['dataid' => $dataid]);
foreach ($fields as $field) {
    error_log("Field: " . $field->name . " (Type: " . $field->type . ")");
}
```

Dann schaue in das Moodle Debug-Log.

## Was tun, wenn Feldnamen nicht stimmen?

Falls z.B. in der Datenbank nicht `NACHNAME` sondern `NAME_NACHNAME` heißt:

1. Öffne `get_anmeldung_data.php`
2. Finde die `$fieldMapping` (ca. Zeile 57)
3. Ersetze den Feldnamen:

```php
// Alt:
'NACHNAME' => 'nachname',

// Neu:
'NAME_NACHNAME' => 'nachname',
```

4. Speichern und fertig!

---

**Hinweis:** Die Feldnamen müssen **exakt** so geschrieben sein wie in der Dataform-Feldliste, inkl. Großbuchstaben/Kleinbuchstaben und Unterstriche.

