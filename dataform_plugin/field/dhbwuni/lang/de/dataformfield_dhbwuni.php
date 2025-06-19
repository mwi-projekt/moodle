<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German strings for DHBW IO university field
 *
 * @subpackage dhbwuni
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'DHBW IO Hochschulauswahl';
$string['fieldtypelabel'] = 'DHBW IO Hochschule';

// Feldkonfiguration
$string['allow_multiple_selections'] = 'Mehrfachauswahl von Hochschulen erlauben';
$string['allow_multiple_selections_help'] = 'Benutzern erlauben, mehrere Hochschulen auszuwählen (z.B. für Prioritätenlisten)';
$string['available_universities'] = 'Verfügbare Hochschulen';
$string['universities_count'] = '{$a} Hochschulen stehen zur Auswahl';
$string['grouping'] = 'Länder-Gruppierung';
$string['universities_grouped_by_country'] = 'Hochschulen werden automatisch nach Ländern gruppiert in der Dropdown-Liste angezeigt';

// Anzeigestrings
$string['choose'] = 'Hochschule auswählen...';
$string['not_selected'] = 'Keine Hochschule ausgewählt';
$string['any'] = 'Beliebige Hochschule';
$string['none'] = 'Keine';

// Fehlermeldungen
$string['fieldrequired'] = 'Bitte wählen Sie eine Hochschule aus';
$string['invaliduniversity'] = 'Die ausgewählte Hochschule ist nicht gültig oder nicht mehr verfügbar';
$string['no_universities_available'] = 'Keine Hochschulen zur Auswahl verfügbar. Bitte fügen Sie zuerst Hochschulen zur DHBW International Office-Aktivität hinzu.';
$string['no_dhbwio_instance'] = 'Keine DHBW International Office-Aktivität in diesem Kurs gefunden. Bitte fügen Sie eine hinzu, bevor Sie diesen Feldtyp verwenden.';
$string['no_dhbwio_instance_desc'] = 'Dieser Feldtyp benötigt eine DHBW International Office-Aktivität im gleichen Kurs, um ordnungsgemäß zu funktionieren.';
$string['invaliddefaultvalue'] = 'Die ausgewählte Standard-Hochschule ist nicht gültig oder nicht mehr verfügbar';
$string['no_universities_for_default'] = 'Keine Hochschulen für Standardauswahl verfügbar';

// Hilfestrings
$string['dhbwuni_help'] = 'Dieses Feld ermöglicht es Studierenden, aus den in der DHBW International Office-Aktivität konfigurierten Hochschulen auszuwählen. Hochschulen werden automatisch nach Ländern gruppiert für bessere Übersichtlichkeit.';

// Datenschutz
$string['privacy:metadata'] = 'Das DHBW IO Hochschul-Feld Plugin speichert die ausgewählte Hochschul-ID als Teil des Dataform-Eintrags.';
$string['privacy:metadata:fieldid'] = 'Die ID der Feldinstanz';
$string['privacy:metadata:entryid'] = 'Die ID des Dataform-Eintrags';
$string['privacy:metadata:content'] = 'Die ausgewählte Hochschul-ID';
$string['privacy:metadata:timecreated'] = 'Der Zeitpunkt der Auswahl';
$string['privacy:metadata:timemodified'] = 'Der Zeitpunkt der letzten Änderung der Auswahl';