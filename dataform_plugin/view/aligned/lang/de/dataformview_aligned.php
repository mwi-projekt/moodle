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
 * @package dataformview_aligned
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Aligned';
$string['aligned:addinstance'] = 'Füge eine neue Aligned Dataform-Ansicht hinzu';
$string['entrytemplate'] = 'Template Eintrag';
$string['entrytemplate_help'] = 'Das Template Eintrag einer Aligned Ansicht ist eine einfache Definition einer Tabellenzeile. Es besteht aus einer Liste an Spaltendefinitionen, wobei jede Spaltendefinition eine neue Zeile steht. Das Format einer Spaltendefinition: Feld-Pattern|Spaltentitel (optional)|Name einer CSS-Klasse (gültig für alle Zellen der Spalte; optional). Beispielsweise zeigt die folgende Definition die Einträge in einer Tabelle ohne Kopfzeile mit 3 Spalten und den spezifizierten Feld-Patterns der Reihe nach an:
<p>
[[Name]]<br />
[[Email]]<br />
[[Nachricht]]<br />
</p>
Die folgende Definition zeigt die Einträge in einer Table mit 5 Spalten und einer Kopfzeile mit Kopftiteln in den ersten 3 SPalten an:
<p>
[[Name]]|Name<br />
[[Email]]|Email<br />
[[Nachricht]]|Nachricht<br />
[[EAC:edit]]]<br />
[[EAC:delete]]<br />
</p>.';
