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
 * @package dataformfield_ratingmdl
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Bewertung (moodle)';
$string['rating'] = 'Bewertung';
$string['usersrating'] = 'User Bewertung';
$string['numratings'] = 'Anzahl Bewertungen';
$string['avgratings'] = 'Durchschnitt Bewertungen';
$string['sumratings'] = 'Summme Bewertungen';
$string['maxratings'] = 'Höchste Bewertung';
$string['minratings'] = 'Niedrigste Bewertung';
/* CAPABILITIES */
$string['ratingmdl:addinstance'] = 'Füge eine neue Bewertung (moodle) als Dataform-Feld hinzu';
$string['ratingmdl:ownrate'] = 'Eigener Eintrag - Rate';
$string['ratingmdl:ownviewaggregate'] = 'Eigener Eintrag - Bewertungen aggregiert anzeigen';
$string['ratingmdl:ownviewratings'] = 'Eigener Eintrag - Bewertungen anzeigen';
$string['ratingmdl:anyrate'] = 'Beliebiger Eintrag - Bewerten';
$string['ratingmdl:anyviewaggregate'] = 'Beliebiger Eintrag - Bewertungen aggregiert anzeigen';
$string['ratingmdl:anyviewratings'] = 'Beliebiger Eintrag - Bewertungen anzeigen';
/* EVENTS */
$string['event_rating_created'] = 'Bewertung erstellt';
$string['event_rating_deleted'] = 'Bewertung gelöscht';
/* SETTINGS */
$string['preventzero'] = 'Verhindere Wertung von 0';
$string['preventzero_help'] = 'Standardmäßig beinhalten Punktskalen den Wert 0 für Bewertungen. Wenn auf Ja gesetzt wird der Wert 0 für Bewertungen nicht verfügbar sein.';
$string['ratelabel'] = 'Rate label';
$string['ratelabel_help'] = 'Label bewerten';
$string['repititionlimit'] = 'Limit Wiederholungen';
$string['repititionlimit_help'] = 'Die maximale Anzahl wie oft ein Wert in der Bewertungsskala durch User im Umfang zur Bewertung des Eintrags genutzt werden kann. (Jeder User ist weiterhin beschränkt auf 1 Bewertung pro Feld)';
$string['repititionscope'] = 'Wiederholungsumfang';
$string['repititionscope_help'] = 'Der Userumfang für den das Wiederholungslimit gilt.';
$string['forceinorder'] = 'Reihenfolge erzwingen';
$string['forceinorder_help'] = 'Wenn eine Reihenfolge erzwungen wird, kann der User keinen bestimmten Wert für eine Bewertung nutzen, bevor vorangegangene Werte im Umfang die durch das Limit vorgegebene Anzahl genutzt wurden (falls zutreffend).';
$string['eachuser'] = 'Jeder User separat';
$string['allusers'] = 'Alle User gesamt';
/* ERRORS */
$string['ratinginvalid1'] = 'Die Anzahl Bewertungen mit dem Wert {$a} hat das Limit erreicht. Bitte wähle einen anderen Wert.';
$string['ratinginvalid2'] = 'Die Reihenfolge der Bewertung gilt. Die erste Bewertung muss der erste Wert im Bewertungsumfang sein.';
$string['ratinginvalid3'] = 'Die Reihenfolge der Bewertung gilt. Dieser Bewertungswert kann nicht zurückgesetzt werden, solange nicht alle aktuellen Bewertungen mit höheren Werten zurückgesetzt sind.';
$string['ratinginvalid4'] = 'Die Reihenfolge der Bewertung gilt. Dein Bewertungswert muss aufeinanderfolgend zur Bewertung mit dem höchsten Wert sein.';
$string['ratinginvalid5'] = 'Die Reihenfolge der Bewertung gilt. Der vorhrtgehende Bewertungswert muss ein Limit erreichen bevor der Wert {$a} zugewiesen werden kann. Bitte wähle den vorhergehenden Wert.';
