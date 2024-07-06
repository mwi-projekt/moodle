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
 * @package dataformfield_entrystate
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Eintrag Status';
$string['entrystate:addinstance'] = 'Füge ein neues Eintrag Status Dataform-Feld hinzu';
$string['state'] = 'Status';
$string['states'] = 'Status';
$string['states_help'] = 'Statusnamen, einer pro Zeile. Beispiel:<p>Entwurf<br />Eingereicht<br />Genehmigt</p>Die Statusliste sollte gespeichert werden, bevor Transitionen hinzugefügt werden können.';
$string['transition'] = 'Transition';
$string['transitions'] = 'Transitionen';
$string['allowedto'] = 'Erlaubt für';
$string['allowedto_help'] = 'Erlaubt für';
$string['notify'] = 'Benachrichtigen';
$string['notify_help'] = 'Benachrichtigen';
$string['stateicon'] = 'Status Icon';
$string['stateicon_help'] = 'Status Icon';
$string['transition'] = 'Transition';
$string['transition_help'] = 'Eine Liste an Status, in die von diesem Status aus gewechselt werden kann. Jeder Status in einer neuen Zeile.';
$string['incorrectstate'] = 'Der angefragte Status {$a} konnte nicht gefunden werden.';
$string['alreadyinstate'] = 'Der Eintrag ({$a->entryid}) ist bereits im gewünschten Status {$a->newstate}.';
$string['instatingdenied'] = 'Dir ist es nicht erlaubt den Status dieses Eintrags zu ändern.';
$string['statechanged'] = 'Der Status der Eintrag ID {$a->id} hat von {$a->old} zu {$a->new} gewechselt.';
