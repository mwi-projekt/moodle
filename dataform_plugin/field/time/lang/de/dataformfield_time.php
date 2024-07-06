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
 * @package dataformfield_time
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Datum/Uhrzeit';
$string['time:addinstance'] = 'Füge ein neues Datum/Uhrzeit Dataform-Feld hinzu';
$string['dateonly'] = 'Nur Datum';
$string['dateonly_help'] = 'Wähle diese Option um nur den Datumsteil des Feldwerts und ein Auswahlfeld nur für das Datum anzuzeigen, wenn das Feld editiert wird.';
$string['displayformat'] = 'Anzeigeformat';
$string['displayformat_help'] = 'Sie können ein individuelles Anzeigeformat den Feldwert setzen. Formatoptionen können unter <a href="http://php.net/manual/en/function.strftime.php">PHP strftime format</a> gefunden werden.';
$string['stopyear'] = 'Endjahr';
$string['stopyear_help'] = 'Wert des Jahres (YYYY). Dieser Wert bestimmt den maximalen Wert des Jahres im Datums-/Uhrzeitauswahlfeld im Editiermodus. Belasse es auf 0 oder leer um den Moodle-Standard zu nutzen.';
$string['fromtimestamp'] = 'Von-Zeitstempel: ';
$string['startyear'] = 'Anfangsjahr';
$string['startyear_help'] = 'Wert des Jahres (YYYY). Dieser Wert bestimmt den minimalen Wert des Jahres im Datums-/Uhrzeitauswahlfeld im Editiermodus. Belasse es auf 0 oder leer um den Moodle-Standard zu nutzen.';
$string['day'] = 'Tag';
$string['month'] = 'Monat';
$string['year'] = 'Jahr';
$string['hour'] = 'Stunde';
$string['minute'] = 'Minute';
$string['masked'] = 'Maskiert';
$string['masked_help'] = 'Wähle diese Option um Uhrzeit-/Datumsauswahlfelddropdowns mit Labeln (z.B. Jahr, Monat, Tag) für leere Werte zu erzeugen. Die Label sind im Sprachpaket definiert.';
