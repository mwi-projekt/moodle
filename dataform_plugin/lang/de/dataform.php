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
 * @package mod
 * $subpackage dataform
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

$string['modulename'] = 'Dataform';
$string['modulename_help'] = 'Das Dataform Modul kann zur Erstellung einer Vielzahl an Aktivitäten/Ressourcen genutzt werden, indem dem Kursleiter/Manager erlaubt wird individuelle Formulare mit verschiedene Eingabeelemente zu designen und erstellen (z.B. Texte, Nummern, Bilder, Dateien, URLs, etc.), und Teilnehmern erlaubt wird Inhalte einzureichen und eingereichte Inhalte anzuzeigen.';
$string['modulenameplural'] = 'Dataforms';

// GENERAL.
$string['dataformnone'] = 'Keine Dataforms gefunden';
$string['dataformnotready'] = 'Diese Aktivität ist noch nicht bereit um angezeigt zu werden';
$string['dataformearly'] = 'Der Beginn dieser Aktivität ist geplant für den {$a}';
$string['dataformpastdue'] = 'Diese Aktivität war offen bis zum {$a}';

// ACTIVITY SETTINGS.
$string['activityadministration'] = 'Administration der Aktivität';
$string['dataformnew'] = 'Neues Dataform';
// Appreance.
$string['activityicon'] = 'Icon der Aktivität';
$string['activityicon_help'] = 'Sie können eine Bildatei hochladen, um das auf der Kursseite neben dem Aktivitätslink angezeigte Standard-Dataform-Aktivitäts-Icon zu ersetzen.';
$string['inlineview'] = 'Inline-Ansicht';
$string['inlineview_help'] = 'Sie können einen dieser Dataform-Ansichten auswählen, um sie auf der Kursseite anstelle des Aktivitätslinks anzeigen. Wenn dies eine neue Instance ist, müssen Sie diese speichern und mindestens eine Ansicht erstellen, bevor Sie die Inline-Ansicht auswählst.';
$string['embedded'] = 'Eingebettet';
$string['embedded_help'] = 'Eine ausgewählte Inline-Ansicht kann auf der Kursseite in einem iframe eingebettet werden um eine Interaktion mit der Ansicht zu ermöglichen, ohne die Kursseite zu verlassen.';
$string['noautocompletioninline'] = 'Autovervollständigung beim Ansehen einer Aktivität kann nicht zusammen mit der Option "Inline-Ansicht" ausgewählt werden';
// Timing.
$string['timing'] = 'Timing and Intervalle';
$string['timeavailable'] = 'Verfügbar ab';
$string['timeavailable_help'] = 'Die vorgesehene Startzeit der Aktivität. Der Zeitpunkt erscheint im Kurskalender. Das Set \'earlyentry\' an Fähigkeiten ermöglicht die Kontrolle der Zugangsberechtigung vor diesem Zeitpunkt.';
$string['timedue'] = 'Fällig bis';
$string['timedue_help'] = 'Die vorgesehene Endzeit der Aktivität. Der Zeitpunkt erscheint im Kurskalender. Das Set \'lateentry\' an Fähigkeiten ermöglicht die Kontrolle der Zugangsberechtigung vor diesem Zeitpunkt.';
$string['timeinterval'] = 'Dauer';
$string['timeinterval_help'] = 'Die Dauer der Aktivität ab ihrer Verfügbar ab Zeit.';
$string['timeinterval_err'] = 'Dauer der Aktivität ist für mehrere Intervalle erforderlich.';
$string['intervalcount'] = 'Anzahl an Intervallen';
$string['intervalcount_help'] = 'Wenn die Aktivität so eingestellt ist, dass sie mehr als ein Intervall hat, gelten die Verfügbarkeitseinstellungen und die Eintragseinstellungen für jedes Intervall separat.';

// Completion.
$string['completionentries'] = 'Teilnehmer müssen Einträge hinzufügen:';
$string['completionentriesgroup'] = 'Einträge verlangen';
$string['completionentrieshelp'] = 'Verlangte Einträge zum Komplettieren';
$string['completionspecificgrade'] = 'Teilnehmer müssen die Note erhalten:';
$string['completionspecificgradegroup'] = 'Bestimmte Note verlangen';
$string['completionspecificgradehelp'] = 'bestimmte Note für den Abschluss erfordern';

// Entries.
$string['entrytype'] = 'Eintragstyp';
$string['entrytype_help'] = 'Sie können eine Eintragsart angeben, wenn Eintragsarten für die Aktivität definiert sind. Die Einträge werden dann nach diesem Typ gefiltert. Neue Einträge, die über eine Ansicht mit dem angegebenen Typ hinzugefügt werden, werden diesem Typ zugewiesen. Sie können Eintragstypen im Feld Eintrag (intern) definieren';
$string['entrytypes'] = 'Eintragstypen';
$string['entrytypes_help'] = 'Sie können Eintragstypen definieren, die dann Ansichten und Filtern zugewiesen werden können, um Ansichten auf bestimmte Eintragstypen zu beschränken. Geben eine durch Kommata getrennte Liste von Namen für Eintragstypen ein. Jeder Name sollte nicht länger als 32 Zeichen sein und aus allgemeinem Klartext bestehen, der mit dem Multilang-Filter kompatibel ist (keine html-Tags).';
$string['entriesmax'] = 'Maximale Anzahl Einträge';
$string['entriesmax_help'] = 'Die maximale Anzahl von Einträgen, die ein Benutzer ohne die Fähigkeit, Einträge zu verwalten, zu der Aktivität hinzufügen kann.
<ul>
<li><b>-1:</b> Unendlich viele Einträge erlaubt.
<li><b> 0:</b> Keine Einträge erlaubt.
<li><b> N:</b> N Einträge erlaubt (wobei N eine positive Nummer ist, z.B. 10).
</ul>
Wenn die Aktivität Intervalle hat, gilt diese Zahl für jedes Intervall, und die maximale Anzahl der Einträge für die gesamte Aktivität ist diese Zahl mal der Anzahl der Intervalle.';
$string['entriesrequired'] = 'Erforderliche Einträge';
$string['entriesrequired_help'] = 'Die Anzahl der Einträge, die ein Benutzer ohne die Fähigkeit, Einträge zu verwalten, hinzufügen muss, damit die Aktivität als abgeschlossen gilt (vor Berücksichtigung anderer Abschlusskriterien wie der Note). Wenn die Aktivität Intervalle hat, gilt diese Zahl für jedes Intervall, und die Anzahl der erforderlichen Einträge für die gesamte Aktivität entspricht dieser Zahl mal der Anzahl der Intervalle.';
$string['groupentries'] = 'Gruppeneinträge';
$string['groupentries_help'] = 'Die Einträge werden mit Gruppeninformationen, aber ohne Autoreninformationen hinzugefügt. Diese Einstellung erfordert den Gruppenmodus.';
$string['anonymousentries'] = 'Erlaube anonyme Einträge';
$string['anonymousentries_help'] = 'Wenn diese Option aktiviert ist, können Gäste und nicht angemeldete Benutzer Einträge in dieser Aktivität vornehmen.
 Dies kann für Anwendungen wie \'contact us\' nützlich sein, bei denen Besucher der Website eine Kontaktanfrage stellen können. Die Option muss vom Administrator in den Moduleinstellungen aktiviert werden.';
$string['entrytimelimit'] = 'Begrenzung der Bearbeitungszeit (Minuteb)';
$string['entrytimelimit_help'] = 'Das Zeitlimit (Minuten), innerhalb dessen ein Benutzer ohne die Fähigkeit, Einträge zu verwalten, einen neuen Eintrag aktualisieren oder löschen kann.
<ul>
<li><b>-1:</b> Unbegrenzt.
<li><b>&nbsp;0:</b> Der Eintrag kann nach der Einreichung nicht mehr aktualisiert oder gelöscht werden.
<li><b>&nbsp;N:</b> Der Eintrag kann innerhalb von N Minuten aktualisiert oder gelöscht werden (wobei N eine beliebige positive Zahl ist, z. B. 30).
</ul>';
$string['notapplicable'] = 'N/A';

// Grading.
$string['gradeguide'] = 'Notenleitfaden/-rubrik';
$string['gradeguide_help'] = 'Wähle einen Notenleitfaden oder eine Bewertungsrubrik aus, der/die für die Vergabe von Noten in dieser Aktivität/Bewertungsaufgabe verwendet werden soll.';
$string['gradecalc'] = 'Notenberechnung';
$string['gradecalc_help'] = 'Eine Notenberechnung ist eine Formel, die die Aktivitätsnote bestimmt. Die Formel kann gängige mathematische Operatoren verwenden, wie z. B. Maximum, Minimum und Summe. Sie kann auch bestimmte Feldmuster verwenden, um die Aktivitätsnote auf der Grundlage des Benutzerinhalts zu bestimmen.';
$string['gradeitems'] = 'Elemente benoten';
$string['gradeitemsin'] = 'Elemente benoten in {$a}';
$string['gradeitems_help'] = 'Auf dieser Seite können Notenelemente für diese Aktivität hinzufügen/bearbeiten.';
$string['usegradeitemsform'] = 'Diese Instanz enthält mehrere Notenelemente. Um diese Elemente zu bearbeiten, verwende bitte <a href="{$a}">Formular Einträge bearbeiten</a>.';

// ROLES.
$string['entriesmanager'] = 'Einträgeverwalter';

$string['dfupdatefailed'] = 'Aktualisierung des Datenformulars fehlgeschlagen!';

$string['fieldtemplate'] = 'Template';
$string['fieldtemplate_help'] = 'Das Feld Template ermöglicht die Angabe eines bestimmten Feldbezeichners, der der Ansicht mit Hilfe des Feldmusters [[Feldname@]] hinzugefügt werden kann. Dieses Feldmuster beachtet die Sichtbarkeit des Feldes und wird ausgeblendet, wenn das Feld als ausgeblendet eingestellt ist. Die Feldvorlage kann auch als Vorlage für die Anzeige von Feldern dienen und interpretiert die Muster des Feldes, wenn sie in der Beschriftung enthalten sind. Beispiel: Bei einem Zahlenfeld mit dem Namen "Guthaben" und dem Feldbezeichner "Sie haben [[Guthaben]]-Guthaben erworben." und einem Eintrag mit dem Zahlenwert 47 würde das Muster [[Guthaben@]] als "Sie haben 47 Guthaben erworben." angezeigt.';
$string['actions'] = 'Eintragsaktionen';
$string['alignment'] = 'Ausrichtung';
$string['ascending'] = 'Aufsteigend';
$string['authorinfo'] = 'Info Autor';
$string['browse'] = 'Durchsuchen';
$string['columns'] = 'Spalten';
$string['commentadd'] = 'Kommentar hinzufügen';
$string['commentbynameondate'] = 'von {$a->name} - {$a->date}';
$string['comment'] = 'Kommentar';
$string['commentdelete'] = 'Sind Sie sicher, dass Sie diesen Kommentar löschen möchten?';
$string['commentdeleted'] = 'Kommentar gelöscht';
$string['commentedit'] = 'Kommentar bearbeiten';
$string['commentempty'] = 'Kommentar war leer';
$string['commentinputtype'] = 'Eingabetyp Kommentar';
$string['commentsallow'] = 'Kommentare erlauben?';
$string['commentsaved'] = 'Kommentar gespeichert';
$string['comments'] = 'Kommentare';
$string['commentsn'] = '{$a} Kommentare';
$string['commentsnone'] = 'Keine Kommentare';
$string['configanonymousentries'] = 'Mit diesem Schalter wird die Möglichkeit von Gast-/Anonymeingaben für alle Datenformulare aktiviert. Sie müssen die Anonymität weiterhin manuell in den Einstellungen für jedes Datenformular aktivieren.';
$string['configenablerssfeeds'] = 'Mit diesem Schalter wird die Möglichkeit von RSS-Feeds für alle Datenformulare aktiviert. Sie müssen immer noch RSS-Ansichten in der Dataform-Instanz hinzufügen, um einen Feed zu erzeugen.';
$string['configmaxentries'] = 'Dieser Wert bestimmt die maximale Anzahl von Einträgen, die zu einer Datenform-Aktivität hinzugefügt werden können.';
$string['configmaxfields'] = 'Dieser Wert bestimmt die maximale Anzahl von Feldern, die einer Datenform-Aktivität hinzugefügt werden können.';
$string['configmaxfilters'] = 'Dieser Wert bestimmt die maximale Anzahl von Filtern, die einer Datenform-Aktivität hinzugefügt werden können.';
$string['configmaxviews'] = 'Dieser Wert bestimmt die maximale Anzahl von Ansichten, die einer Datenform-Aktivität hinzugefügt werden können.';
$string['correct'] = 'Richtig';
$string['csscode'] = 'CSS-Code';
$string['cssinclude'] = 'CSS';
$string['cssincludes'] = 'Externes CSS einfügen';
$string['csssaved'] = 'CSS gespeichert';
$string['cssupload'] = 'Lade CSS-Dateien hoch';

// GRADE.
$string['multigradeitems'] = 'Erlaube mehrfache Notenelemente';
$string['configmultigradeitems'] = 'Setzen Sie diese Option auf "Ja", um mehrere Notenelemente in einer Dataform-Aktivität zuzulassen.';

// CSV.
$string['csvdelimiter'] = 'Trennzeichen';
$string['csvenclosure'] = 'Umschließendes Zeichen';
$string['csvfailed'] = 'Die Rohdaten können nicht aus der CSV-Datei gelesen werden';
$string['csvoutput'] = 'Ausgabe CSV';
$string['csvsettings'] = 'Einstellungen CSV';
$string['csvwithselecteddelimiter'] = '<acronym title=\"Comma Separated Values\">CSV</acronym> Text mit ausgewähltem Trennzeichen:';

// RESET.
$string['deletenotenrolled'] = 'Einträge von nicht registrierten Benutzern löschen';

$string['descending'] = 'Absteigend';

$string['documenttype'] = 'Dokumententyp';
$string['dots'] = '...';
$string['download'] = 'Download';
$string['editordisable'] = 'Editor deaktivieren';
$string['editorenable'] = 'Editor aktivieren';
$string['embed'] = 'Einbetten';
$string['enabled'] = 'Aktivieren';
$string['entriesadded'] = '{$a} Eintrag/Einträge hinzugefügt';
$string['entriesconfirmadd'] = 'Sie sind dabei, {$a} Eintrag(e) zu duplizieren. Möchten Sie fortfahren?';
$string['entriesconfirmduplicate'] = 'Sie sind dabei, {$a} Eintrag/Einträge zu duplizieren. Möchten Sie fortfahren?';
$string['entriesconfirmdelete'] = 'Sie sind dabei, {$a} Eintrag/Einträge zu löschen. Möchten Sie fortfahren?';
$string['entriesconfirmupdate'] = 'Sie sind dabei, {$a} Eintrag/Einträge zu aktualisieren. Möchten Sie fortfahren?';
$string['entriescount'] = '{$a} Eintrag/Einträge';
$string['entriesdeleteall'] = 'Lösche alle Einträge';
$string['entriesdeleted'] = '{$a} Einträge gelöscht';
$string['entriesduplicated'] = '{$a} Einträge dupliziert';
$string['entries'] = 'Einträge';
$string['entriesfound'] = '{$a} Einträge gefunden';
$string['entriesimport'] = 'Importiere Einträge';
$string['entrieslefttoaddtoview'] = 'Sie müssen {$a} weitere(n) Eintrag/Einträge hinzufügen, bevor Sie die Einträge anderer Teilnehmer einsehen können.';
$string['entrieslefttoadd'] = 'Sie müssen {$a} weitere(n) Eintrag/Einträge hinzufügen, um diese Aktivität abzuschließen';
$string['entriesnotsaved'] = 'Es wurde kein Eintrag gespeichert. Bitte überprüfen Sie das Format der hochgeladenen Datei.';
$string['entriespending'] = 'Austehend';
$string['entriesupdated'] = '{$a} Eintrag/Einträge aktualisiert';
$string['entriessaved'] = '{$a} Eintrag/Einträge gespeichert';
$string['entryaddmultinew'] = 'Neue Einträge hinzufügen';
$string['entryaddnew'] = 'Neuen Eintrag hinzufügen';
$string['entry'] = 'Eintrag';
$string['entryinfo'] = 'Info Eintrag';
$string['entrynew'] = 'Neuer Eintrag';
$string['entrynoneforaction'] = 'Es wurden keine Einträge für die angeforderte Aktion gefunden';
$string['entrynoneindataform'] = 'Keine Einträge im Dataform';
$string['entryrating'] = 'Bewertung Eintrag';
$string['entrysaved'] = 'Dein Eintrag wurde gespeichert';
$string['entrysettings'] = 'Einstellungen Eintrag';
$string['entrysettingsupdated'] = 'Einstellungen Einträge aktualisiert';
$string['exportcontent'] = 'Exportiere Inhalt';
$string['export'] = 'Export';

$string['firstdayofweek'] = 'Montag';
$string['first'] = 'Erster';
$string['formemptyadd'] = 'Sie haben keine Felder ausgefüllt!';
$string['fromfile'] = 'Importiere aus ZIP-Datei';
$string['generalactions'] = 'Allgemeine Maßnahmen';
$string['getstarted'] = 'Dieses Datenformular scheint neu oder unvollständig eingerichtet zu sein.';
$string['getstartedpresets'] = 'Anwenden einer Voreinstellung im Abschnitt {$a}';
$string['getstartedfields'] = 'Felder im Abschnitt {$a} hinzufügen';
$string['getstartedviews'] = 'Ansichten im Abschnitt {$a} hinzufügen';

$string['headercss'] = 'Benutzerdefinierte CSS-Stile für alle Ansichten';
$string['headerjs'] = 'Benutzerdefiniertes JavaScript für alle Ansichten';
$string['horizontal'] = 'Horizontal';
$string['importadd'] = 'Hinzufügen einer neuen Importansicht';
$string['import'] = 'Import';
$string['importnoneindataform'] = 'Es sind keine Importe für dieses Dataform definiert.';
$string['incorrect'] = 'Falsch';
$string['index'] = 'Index';
$string['insufficiententries'] = 'weitere Eingaben erforderlich, um dieses Dataform anzuzeigen';
$string['internal'] = 'Intern';
$string['intro'] = 'Einführung';
$string['invalidname'] = 'Bitte wählen Sie einen anderen Namen für diesen {$a}';
$string['invalidrate'] = 'Ungültige Bewertung im Dataform ({$a})';
$string['invalidurl'] = 'Die gerade eingegebene URL ist ungültig';
$string['jscode'] = 'JavaScript code';
$string['jsinclude'] = 'JS';
$string['jsincludes'] = 'Externes JavaScript einbinden';
$string['jssaved'] = 'JavaScript gespeichert';
$string['jsupload'] = 'Hochladen von JavaScript-Dateien';
$string['lock'] = 'Sperren';
$string['manage'] = 'Verwalten';
$string['mappingwarning'] = 'Alle alten Felder, die nicht einem neuen Feld zugeordnet sind, gehen verloren und alle Daten in diesem Feld werden gelöscht.';
$string['max'] = 'Maximum';
$string['maxsize'] = 'Maximale Größe';
$string['mediafile'] = 'Medien-Datei';
$string['reference'] = 'Referenz';

// DATAFORM..
$string['min'] = 'Minimum';
$string['more'] = 'Mehr';
$string['moreurl'] = 'Mehr URL';
$string['movezipfailed'] = 'Die ZIP-Datei kann nicht verschoben werden';
$string['multidelete'] = 'Löschen';
$string['multidownload'] = 'Herunterladen';
$string['multiduplicate'] = 'Duplizieren';
$string['multiedit'] = 'Bearbeiten';
$string['multiexport'] = 'Exportieren';
$string['multishare'] = 'Teilen';
$string['newvalueallow'] = 'Neue Werte zulassen';
$string['newvalue'] = 'Neuer Wert';
$string['noaccess'] = 'Sie haben keinen Zugriff auf diese Seite';
$string['nomatch'] = 'Keine übereinstimmenden Einträge gefunden!';
$string['nomaximum'] = 'Kein Maximum';
$string['notopenyet'] = 'Diese Aktivität ist leider nicht verfügbar bis {$a}';

// EVENT.
$string['event'] = 'Ereignis';
$string['events'] = 'Ereignisse';

$string['event_view_created'] = 'Ansicht erstellt';
$string['event_view_updated'] = 'Ansicht aktualisiert';
$string['event_view_deleted'] = 'Ansicht gelöscht';
$string['event_view_viewed'] = 'Ansicht aufgerufen';

$string['event_field_created'] = 'Feld erstellt';
$string['event_field_updated'] = 'Feld aktualisiert';
$string['event_field_deleted'] = 'Feld gelöscht';
$string['event_field_content_updated'] = 'Feldinhalt aktualisiert';

$string['event_filter_created'] = 'Filter erstellt';
$string['event_filter_updated'] = 'Filter aktualisiert';
$string['event_filter_deleted'] = 'Filter gelöscht';

$string['event_entry_created'] = 'Eintrag erstellt';
$string['event_entry_updated'] = 'Eintrag aktualisiert';
$string['event_entry_deleted'] = 'Eintrag gelöscht';

// CAPABILITY.
// Deprecated.
$string['dataform:viewaccesshidden'] = '** Deprecated **';
$string['dataform:viewanonymousentry'] = '** Deprecated **';
$string['dataform:viewentry'] = '** Deprecated **';
$string['dataform:writeentry'] = '** Deprecated **';
$string['dataform:exportallentries'] = '** Deprecated **';
$string['dataform:exportentry'] = '** Deprecated **';
$string['dataform:exportownentry'] = '** Deprecated **';
$string['dataform:manageratings'] = '** Deprecated **';
$string['dataform:rate'] = '** Deprecated **';
$string['dataform:ratingsviewall'] = '** Deprecated **';
$string['dataform:ratingsviewany'] = '** Deprecated **';
$string['dataform:ratingsview'] = '** Deprecated **';
$string['dataform:comment'] = '** Deprecated **';
$string['dataform:managecomments'] = '** Deprecated **';
$string['dataform:approve'] = '** Deprecated **';

// Dataform.
$string['dataform:addinstance'] = 'Eine neue Datenformular-Aktivität hinzufügen';
$string['dataform:indexview'] = 'Index anzeigen';
$string['dataform:messagingview'] = 'Messaging anzeigen';
$string['dataform:managetemplates'] = 'Vorlagen verwalten';
$string['dataform:manageviews'] = 'Ansichten verwalten';
$string['dataform:managefields'] = 'Felder verwalten';
$string['dataform:managefilters'] = 'Filter verwalten';
$string['dataform:manageaccess'] = 'Zugriffsregeln verwalten';
$string['dataform:managenotifications'] = 'Benachrichtigungsregeln verwalten';
$string['dataform:managecss'] = 'CSS verwalten';
$string['dataform:managejs'] = 'JS verwalten';
$string['dataform:managetools'] = 'Werkzeuge verwalten';

// Presets.
$string['dataform:managepresets'] = 'Presets verwalten';
$string['dataform:presetsviewall'] = 'Presets von allen Benutzern anzeigen';

// View.
$string['dataform:viewaccess'] = 'Ansicht - Zugriff';
$string['dataform:viewaccessdisabled'] = 'Ansicht - Zugriff deaktiviert';
$string['dataform:viewaccessearly'] = 'Ansicht - früher Zugriff';
$string['dataform:viewaccesslate'] = 'Ansicht - später Zugriff';
$string['dataform:viewfilteroverride'] = 'Ansicht - Filter außer Kraft setzen';

// Entries.
$string['dataform:manageentries'] = 'Einträge verwalten';

$string['dataform:entryearlyview'] = 'Früher Eintrag - Ansicht';
$string['dataform:entryearlyadd'] = 'Früher Eintrag - Hinzufügen';
$string['dataform:entryearlyupdate'] = 'Früher Eintrag - Aktualisierung';
$string['dataform:entryearlydelete'] = 'Früher Eintrag - Löschen';

$string['dataform:entrylateview'] = 'Später Eintrag - Ansicht';
$string['dataform:entrylateadd'] = 'Später Eintrag - Hinzufügen';
$string['dataform:entrylateupdate'] = 'Später Eintrag - Aktualisieren';
$string['dataform:entrylatedelete'] = 'Später Eintrag - Löschen';

$string['dataform:entryownview'] = 'Eigener Eintrag - Ansicht';
$string['dataform:entryownexport'] = 'Eigener Eintrag - Exportieren';
$string['dataform:entryownadd'] = 'Eigener Eintrag - Hinzufügen';
$string['dataform:entryownupdate'] = 'Eigener Eintrag - Aktualisieren';
$string['dataform:entryowndelete'] = 'Eigener Eintrag - Löschen';

$string['dataform:entrygroupview'] = 'Gruppeneintrag - Ansicht';
$string['dataform:entrygroupexport'] = 'Gruppeneintrag - Exportieren';
$string['dataform:entrygroupadd'] = 'Gruppeneintrag - Hinzufügen';
$string['dataform:entrygroupdate'] = 'Gruppeneintrag - Aktualisieren';
$string['dataform:entrygroupdelete'] = 'Gruppeneintrag - Löschen';

$string['dataform:entryanyview'] = 'Beliebiger Eintrag - Ansicht';
$string['dataform:entryanyexport'] = 'Beliebiger Eintrag - Exportieren';
$string['dataform:entryanyadd'] = 'Beliebiger Eintrag - Hinzufügen';
$string['dataform:entryanyupdate'] = 'Beliebiger Eintrag - Aktualisieren';
$string['dataform:entryanydelete'] = 'Beliebiger Eintrag - Löschen';

$string['dataform:entryanonymousview'] = 'Anonymer Eintrag - Ansicht';
$string['dataform:entryanonymousexport'] = 'Anonymer Eintrag - Exportieren';
$string['dataform:entryanonymousadd'] = 'Anonymer Eintrag - Hinzufügen';
$string['dataform:entryanonymousupdate'] = 'Anonymer Eintrag - Aktualisieren';
$string['dataform:entryanonymousdelete'] = 'Anonymer Eintrag - Löschen';

// MESSAGE.
$string['messageprovider:dataform_notification'] = 'Datenformular-Benachrichtigungen';
$string['subject'] = 'Betreff';
$string['message'] = 'Nachricht';
$string['contentview'] = 'Inhalt der Ansicht';
$string['notification'] = 'Benachrichtigung';
$string['conversation'] = 'Konversation';
$string['noreply'] = 'No-reply';
$string['absender'] = 'Absender';
$string['recipient'] = 'Empfänger';

// VIEW.
$string['viewadd'] = 'Eine Ansicht hinzufügen';
$string['viewcreate'] = 'Eine neue Ansicht erstellen';
$string['viewcurrent'] = 'Aktuelle Ansicht';
$string['viewcustomdays'] = 'Benutzerdefiniertes Aktualisierungsintervall: Tage';
$string['viewcustomhours'] = 'Benutzerdefiniertes Aktualisierungsintervall: Stunden';
$string['viewcustomminutes'] = 'Benutzerdefiniertes Aktualisierungsintervall: Minuten';
$string['viewdescription'] = 'Beschreibung der Ansicht';
$string['viewdescription_help'] = 'Kurze Beschreibung des Zwecks und der Merkmale der Ansicht, damit Manager auf einen Blick sehen können, wofür jede Ansicht gedacht ist. Diese Beschreibung wird nur in der Ansichtsverwaltungsliste angezeigt.';
$string['viewedit'] = 'Bearbeitung \'{$a}\'';
$string['vieweditthis'] = 'Diese Ansicht bearbeiten';
$string['viewfilter'] = 'Filter';
$string['viewfilter_help'] = 'Ein vordefinierter Filter aus der Liste Filters (falls vorhanden), der in der Ansicht erzwungen wird. ';
$string['viewforedit'] = 'Ansicht für \'edit\'';
$string['viewformore'] = 'Ansicht für \'more\'';
$string['viewfromdate'] = 'Einsehbar von';
$string['viewintervalsettings'] = 'Intervalleinstellungen';
$string['viewinterval'] = 'Wann wird der Inhalt der Ansicht aktualisiert';
$string['entrytemplate'] = 'Eintrags-Template';
$string['entrytemplate_help'] = 'Mit dem Eintrags-Template können Sie den Inhalt, das Verhalten und das allgemeine Layout eines Eintrags sowohl für das Durchsuchen als auch für die Bearbeitung festlegen. Dieses Template enthält in der Regel Feldelemente für die Anzeige und Aktualisierung des Eintragsinhalts. Beim Erstellen einer neuen Ansicht wird Dieses Template automatisch mit einem Standardlayout gefüllt, das aus Basisfeldmustern, einer Bearbeitungsaktion und einer Löschaktion besteht. Sie können dann nach Bedarf Pattern hinzufügen oder entfernen. Mit dem WYSIWYG-Editor können Sie das Template auch ausschmücken und mit Farbe, Schriftart und Bildern Ihr eigenes Aussehen gestalten.';
$string['viewname'] = 'Name der Ansicht';
$string['viewname_help'] = 'Kurzer Name für die Ansicht. Der Ansichtsname kann in bestimmten Ansichts-Pattern verwendet werden. In diesem Fall sollte das Format des Namens so einfach wie möglich sein, nur alpha- oder alphanumerische Zeichen, um Probleme beim Parsen von Patterns zu vermeiden.';
$string['viewnew'] = 'Neue {$a} Ansicht';
$string['viewnodefault'] = 'Standardansicht ist nicht festgelegt. Wählen Sie eine der Ansichten in der {$a} Liste als Standardansicht.';
$string['viewnoneforaction'] = 'Es wurden keine Ansichten für die angeforderte Aktion gefunden';
$string['viewnoneindataform'] = 'Es sind keine Ansichten für dieses Datenformular definiert';
$string['viewoptions'] = 'Ansichtsoptionen';
$string['viewpagingfield'] = 'Paging-Feld';
$string['viewperpage'] = 'Pro Seite';
$string['viewperpage_help'] = 'Die maximale Anzahl der Einträge, die in der Ansicht zu einem bestimmten Zeitpunkt angezeigt werden sollen. Wenn die Anzahl der anzeigbaren Einträge höher ist als die gewählte pro Seite, wird eine Paging-Bar angezeigt (vorausgesetzt, die ##paging:bar## ist der Ansichtsvorlage hinzugefügt).';
$string['viewresettodefault'] = 'Auf Standard zurücksetzen';
$string['viewreturntolist'] = 'Zur Liste zurückkehren';
$string['viewsadded'] = '{$a} Ansicht(en) hinzugefügt';
$string['viewsconfirmdelete'] = 'Sie sind dabei {$a} Ansicht(en) zu löschen. Möchten Sie fortfahren?';
$string['viewsconfirmduplicate'] = 'Sie sind im Begriff {$a} Ansicht(en) zu duplizieren. Möchten Sie fortfahren?';
$string['viewsdeleted'] = '{$a} view(s) deleted';
$string['viewtemplate'] = 'Ansichts-Template';
$string['viewtemplate_help'] = 'Das Ansichts-Template ermöglicht es Ihnen, den Inhalt, das Verhalten und das allgemeine Layout einer Ansichtsseite in der Dataform-Aktivität zu bestimmen. Dieses Template enthält typischerweise Elemente für die Aktivitätsnavigation, die Suche und Sortierung sowie die Anzeige der Einträge. Wenn Sie eine neue Ansicht erstellen, wird das Template automatisch mit einem Standardlayout gefüllt und Sie können dann nach Bedarf Elemente hinzufügen oder entfernen. Mit dem WYSIWYG-Editor können Sie das Template auch ausschmücken und mit Farben, Schriftarten und Bildern Ihr eigenes Aussehen gestalten.';
$string['viewtiming'] = 'Anzeigezeitpunkt';
$string['viewtiming_help'] = 'Legen Sie die Daten von und/oder bis fest, um den Zugriff auf die Ansicht nur zwischen den angegebenen Daten für alle Personen ohne Zugriffsmöglichkeit zu beschränken. Aktivieren Sie das Kontrollkästchen \'Als Standard festlegen\', um die Ansicht als Standardansicht festzulegen, wenn sie verfügbar ist.';
$string['viewgeneral'] = 'Allgemeine Einstellungen der Ansicht';
$string['viewgeneral_help'] = 'Allgemeine Einstellungen der Ansicht';
$string['viewsectionpos'] = 'Abschnittsposition';
$string['viewslidepaging'] = 'Diaseitenwechsel';
$string['viewsmax'] = 'Maximale Ansichten';
$string['viewsupdated'] = '{$a} Ansicht(en) aktualisiert';
$string['views'] = 'Ansichten';
$string['Ansicht'] = 'Ansicht';
$string['viewvisibility'] = 'Sichtbarkeit';
$string['viewvisibility_help'] = 'Auf eine deaktivierte Ansicht kann nur mit der Fähigkeit viewaccessdisabled zugegriffen werden (in der Regel Lehrer und Manager).
Auf eine sichtbare Ansicht können Teilnehmer zugreifen und sie wird in der Navigation angezeigt.
Auf eine verborgene Ansicht können die Teilnehmer zugreifen, sie wird jedoch nicht in der Navigation angezeigt.';
$string['viewdisabled'] = 'Deaktiviert';
$string['viewvisible'] = 'Sichtbar';
$string['viewhidden'] = 'Versteckt';

$string['wrongdataid'] = 'Falsche Datenform-ID angegeben';
$string['submission'] = 'Einreichung';

// VIEW SETTING.
$string['editing'] = 'Editieren';
$string['modeeditonly'] = 'Nur bearbeitete Einträge';
$string['modeeditseparate'] = 'Bearbeitete Einträge getrennt von anderen Einträgen';
$string['modeeditinline'] = 'Bearbeitete Einträge zusammen mit anderen Einträgen';
$string['submissiondisplay'] = 'Anzeige beim Bearbeiten';
$string['availablefrom'] = 'Verfügbar von';
$string['availableto'] = 'Verfügbar für';
$string['savebutton'] = 'Speichern';
$string['savebutton_label'] = 'Speicher-Button';
$string['savebutton_help'] = 'Speichert den bearbeiteten Eintrag und kehrt zur Ansicht zurück.';
$string['savecontbutton'] = 'Speichern und weiter';
$string['savecontbutton_label'] = 'Speichern und Weiter-Button';
$string['savecontbutton_help'] = 'Speichert den bearbeiteten Eintrag und bleibt im Formular, um den Eintrag weiter zu bearbeiten.';
$string['savecontnewbutton'] = 'Speichern und Neu beginnen';
$string['savecontnewbutton_label'] = 'Speichern und Neu beginnen-Button';
$string['savecontnewbutton_help'] = 'Speichert den bearbeiteten Eintrag und öffnet ein neues Eingabeformular.';
$string['savenewbutton'] = 'Als Neu speichern';
$string['savenewbutton_label'] = 'Als Neu speichern-Button';
$string['savenewbutton_help'] = 'Speichert den bearbeiteten Eintrag als neuen Eintrag (der ursprüngliche Eintrag wird nicht verändert) und kehrt zur Ansicht zurück.';
$string['savenewcontbutton'] = 'Als Neu speichern und Weiter';
$string['savenewcontbutton_label'] = 'Neu speichern und Weiter-Button';
$string['savenewcontbutton_help'] = 'Speichert den bearbeiteten Eintrag als neuen Eintrag (der ursprüngliche Eintrag wird nicht geändert) und bleibt im Formular, um die Bearbeitung des neuen Eintrags fortzusetzen.';
$string['cancelbutton'] = 'Abbrechen';
$string['cancelbutton_label'] = 'Abbrechen-Button';
$string['cancelbutton_help'] = 'Bricht die Anmeldung ab und kehrt zur Ansicht zurück';
$string['submissionredirect'] = 'Zu einer anderen Ansicht umleiten';
$string['submissionredirect_help'] = 'Standardmäßig bleibt der Benutzer nach der Eingabe in der gleichen Ansicht. Wenn eine andere Ansicht ausgewählt wird, wird der Benutzer nach dem Absenden zu dieser Ansicht umgeleitet.';
$string['submissiontimeout'] = 'Antwortzeitüberschreitung';
$string['submissiontimeout_help'] = 'Die Verzögerung (in Sekunden) nach dem Absenden und bevor der Benutzer zur Zielansicht umgeleitet wird.';
$string['submissionmessage'] = 'Antwort auf die Übermittlung';
$string['submissionmessage_help'] = 'Eine Nachricht, die dem Benutzer nach erfolgreicher Übermittlung und vor der Weiterleitung zur Zielansicht angezeigt wird.';
$string['submissiondefaultmessage'] = 'Vielen Dank';
$string['submitfailure'] = 'Ihre Anmeldung konnte nicht gespeichert werden. Bitte versuchen Sie es erneut.';
$string['submissiondisplayafter'] = 'Nur bearbeitete Einträge anzeigen';
$string['submissiondisplayafter_help'] = 'Standardmäßig werden alle sichtbaren Einträge nach der Übermittlung angezeigt. Setzen Sie diesen Wert auf \'Yes\', wenn Sie nur die bearbeiteten Einträge anzeigen möchten.';

$string['patternsreplacement'] = 'Pattern-Ersatz';

// FIELDS.
$string['fieldadd'] = 'Ein Feld hinzufügen';
$string['fieldallowautolink'] = 'Autolink zulassen';
$string['fieldattributes'] = 'Feldattribute';
$string['fieldcreate'] = 'Ein neues Feld erstellen';
$string['fielddescription'] = 'Feldbeschreibung';
$string['fieldeditable'] = 'Bearbeitbar';
$string['fieldedit'] = 'Bearbeitung \'{$a}\'';
$string['field'] = 'Feld';
$string['fieldids'] = 'Feld-IDs';
$string['fieldmappings'] = 'Feld-Zuordnungen';
$string['fieldname'] = 'Feldname';
$string['fieldnew'] = 'Neues {$a} Feld';
$string['fieldnoneforaction'] = 'Es wurden keine Felder für die angeforderte Aktion gefunden';
$string['fieldnoneindataform'] = 'Es sind keine Felder für dieses Datenformular definiert';
$string['fieldnonematching'] = 'Es wurden keine passenden Felder gefunden';
$string['fieldnotmatched'] = 'Die folgenden Felder in Ihrer Datei sind in diesem Datenformular nicht bekannt: {$a}';
$string['fieldrequired'] = 'Sie müssen hier einen Wert angeben.';
$string['fieldrules'] = 'Feldbearbeitungsregeln';
$string['fieldsadded'] = 'Felder hinzugefügt';
$string['fieldsconfirmdelete'] = 'Sie sind dabei {$a} Feld(er) zu löschen. Möchten Sie fortfahren?';
$string['fieldsconfirmduplicate'] = 'Sie sind im Begriff {$a} Feld(er) zu duplizieren. Möchten Sie fortfahren?';
$string['fieldsdeleted'] = 'Felder gelöscht. Sie müssen möglicherweise die Standardsortiereinstellungen aktualisieren.';
$string['fields'] = 'Felder';
$string['fieldsinternal'] = 'Interne Felder';
$string['fieldsmax'] = 'Maximale Felder';
$string['fieldsnonedefined'] = 'Keine Felder definiert';
$string['fieldsupdated'] = 'Felder aktualisiert';
$string['fieldvisibility'] = 'Sichtbar für';
$string['fieldvisibleall'] = 'Alle';
$string['fieldvisiblenone'] = 'Nur für Manager';
$string['fieldvisibleowner'] = 'Eigentümer und Manager';
$string['fieldwidth'] = 'Breite';
$string['err_lowername'] = 'Der Name darf keine Großbuchstaben enthalten.';
$string['fielddefaultcontent'] = 'Standardinhalt';
$string['fielddefaultvalue'] = 'Standardwert';
$string['fieldapplydefault'] = 'Standardwert bei Bearbeitung anwenden';
$string['fielddefaultnew'] = 'Nur neue Einträge';
$string['fielddefaultany'] = 'Beliebiger Eintrag';

$string['filesettings'] = 'Dateieinstellungen';
$string['filemaxsize'] = 'Gesamtgröße der hochgeladenen Dateien';
$string['filesmax'] = 'Maximale Anzahl der hochgeladenen Dateien';
$string['filetypeany'] = 'Beliebiger Dateityp';
$string['filetypeaudio'] = 'Audio-Dateien';
$string['filetypegif'] = 'gif-Dateien';
$string['filetypehtml'] = 'Html-Dateien';
$string['filetypeimage'] = 'Bilddateien';
$string['filetypejpg'] = 'jpg-Dateien';
$string['filetypepng'] = 'png-Dateien';
$string['filetypes'] = 'Akzeptierte Dateitypen';

// FILTER.
$string['filtersortfieldlabel'] = 'Sortierfeld ';
$string['filtersearchfieldlabel'] = 'Suchfeld';
$string['filteradvanced'] = 'Erweiterter Filter';
$string['filteradd'] = 'Einen Filter hinzufügen';
$string['filterbypage'] = 'Nach Seite';
$string['filtercancel'] = 'Filter aufheben';
$string['filtercreate'] = 'Einen neuen Filter erstellen';
$string['filtercurrent'] = 'Aktueller Filter';
$string['filtercustomsearch'] = 'Suchoptionen';
$string['filtercustomsort'] = 'Sortieroptionen';
$string['filterdescription'] = 'Filterbeschreibung';
$string['filteredit'] = 'Bearbeitung \'{$a}\'';
$string['filter'] = 'Filter';
$string['filtergroupby'] = 'Gruppieren nach';
$string['filterincomplete'] = 'Suchbedingung muss erfüllt sein.';
$string['filtername'] = 'Datenform Auto-Verknüpfung';
$string['filternew'] = 'Neuer Filter';
$string['filternoneforaction'] = 'Es wurden keine Filter für die angeforderte Aktion ({$a}) gefunden';
$string['filterperpage'] = 'Pro Seite';
$string['filtersadded'] = '{$a} Filter hinzugefügt';
$string['filtersave'] = 'Filter speichern';
$string['filtersconfirmdelete'] = 'Sie sind dabei {$a} Filter zu löschen. Möchten Sie fortfahren?';
$string['filtersconfirmduplicate'] = 'Sie sind im Begriff, {$a} Filter zu duplizieren. Möchten Sie fortfahren?';
$string['filtersdeleted'] = '{$a} Filter wurde(n) gelöscht';
$string['filtersduplicated'] = '{$a} Filter wurde(n) dupliziert';
$string['filterselection'] = 'Auswahl';
$string['filters'] = 'Filter';
$string['filtersimplesearch'] = 'Einfache Suche';
$string['filtersmax'] = 'Maximale Filter';
$string['filtersnonedefined'] = 'Keine Filter definiert';
$string['filtersnoneindataform'] = 'Es sind keine Filter für dieses Datenformular definiert';
$string['filtersupdated'] = '{$a} Filter wurde(n) aktualisiert';
$string['filterupdate'] = 'Einen vorhandenen Filter aktualisieren';
$string['filterurlquery'] = 'Url-Abfrage';
$string['filtersaved'] = 'Meine gespeicherten Filter';
$string['filtersavedreset'] = '* Gespeicherte Filter zurücksetzen';
$string['filterquick'] = 'Schnellfilter';
$string['filterquickreset'] = '* Schnellfilter zurücksetzen';

// FILTER OPERATORS.
$string['empty'] = 'Leer';
$string['equal'] = 'Gleich';
$string['greaterthan'] = 'Größer als';
$string['lessthan'] = 'Kleiner als';
$string['greaterorequal'] = 'Größer oder gleich';
$string['lessorequal'] = 'Kleiner oder gleich';
$string['between'] = 'Zwischen';
$string['enthält'] = 'Enthält';
$string['in'] = 'In';
$string['andor'] = 'und/oder';
$string['and'] = 'UND';
$string['or'] = 'ODER';
$string['is'] = 'IST';
$string['not'] = 'NICHT';

// RULE.
$string['ruleadd'] = 'Eine Regel hinzufügen';
$string['rulenew'] = 'Neue {$a} Regel';
$string['rule'] = 'Regel';
$string['rules'] = 'Regeln';
$string['ruleenabled'] = 'Aktiviert';
$string['rulesnone'] = 'Keine Regeln';
$string['scope'] = 'Bereich';
$string['acccesstypesnotfound'] = 'Leider sind keine Zugriffsarten installiert oder aktiviert. Bitte kontaktieren Sie Ihren Administrator für weitere Informationen.';
$string['notificationtypesnotfound'] = 'Leider sind keine Benachrichtigungstypen installiert oder aktiviert. Bitte wenden Sie sich für weitere Informationen an Ihren Administrator.';

// ACCESS.
$string['access'] = 'Zugang';
$string['accessadd'] = 'Zugriffskontext hinzufügen';
$string['accessit'] = 'Bearbeiten {$a}';
$string['accessnew'] = 'Neuer {$a}';
$string['accessessenabled'] = 'Aktiviert';
$string['errornoitemsselected'] = 'Mindestens ein Element sollte ausgewählt sein';
$string['errorinvalidtimeto'] = 'Time-to muss größer als Time-from sein';

// TOOL.
$string['tools'] = 'Werkzeuge';
$string['toolnoneindataform'] = 'Für diese Dataform-Aktivität sind keine Werkzeuge definiert.';
$string['toolrun'] = 'Ausführen';

$string['muster'] = 'Patterns';
$string['patternsnonebroken'] = 'Keine fehlerhaften Pattern gefunden';
$string['musterungültig'] = 'Pattern gültig';
$string['mustergebrochen'] = 'Fehlerhafte Pattern gefunden';
$string['patternsuspect'] = 'Verdächtige Pattern gefunden';
$string['patterncleanup'] = 'Aufräumen';
$string['characterpatterns'] = 'Zeichen-Patter';
$string['characterpatterns_help'] = 'Zeichen-Pattern';
$string['fieldpatterns'] = 'Eingabe-Pattern';
$string['fieldpatterns_help'] = 'Pattern, deren Inhalt eingabeabhängig ist';
$string['viewpatterns'] = 'Ansichts-Pattern';
$string['viewpatterns_help'] = 'Pattern, deren Inhalt typischerweise nicht eingabeabhängig ist';

// PRESET.
$string['presetadd'] = 'Preset hinzufügen';
$string['presetapply'] = 'Anwenden';
$string['presetavailableincourse'] = 'Kurs-Preset';
$string['presetavailableinsite'] = 'Seiten-Preset';
$string['presetchoose'] = 'wähle ein vordefiniertes Preset';
$string['presetdataanon'] = 'mit anonymisierten Benutzerdaten';
$string['presetdata'] = 'mit Benutzerdaten';
$string['presetfaileddelete'] = 'Fehler beim Löschen eines Preset!';
$string['presetfromdataform'] = 'Mache ein Preset von dieser Dataform-Aktivität';
$string['presetfromfile'] = 'Preset aus Datei hochladen';
$string['presetimportsuccess'] = 'Das Preset wurde erfolgreich angewendet';
$string['presetinfo'] = 'Durch das Speichern als Preset wird diese Ansicht veröffentlicht. Andere Benutzer können sie dann in ihren Dataform-Aktivitäten verwenden.';
$string['presetmap'] = 'Felder zuordnen';
$string['presetnodata'] = 'ohne Benutzerdaten';
$string['presetnodefinedfields'] = 'Neues Preset hat keine definierten Felder!';
$string['presetnodefinedviews'] = 'Neues Preset hat keine definierten Ansichten!';
$string['presetnoneavailable'] = 'Keine verfügbaren Presets zum Anzeigen';
$string['presetplugin'] = 'Plug in';
$string['presetrefreshlist'] = 'Liste aktualisieren';
$string['presetshare'] = 'Freigeben';
$string['presetsharesuccess'] = 'Erfolgreich gespeichert. Ihr Preset wird nun auf der gesamten Website bereitgestellt.';
$string['presetsource'] = 'Preset-Quelle';
$string['voreinstellungen'] = 'Presets';
$string['presetusestandard'] = 'Ein Preset verwenden';

$string['page-mod-dataform-x'] = 'Beliebige Dataform-Aktivitätsmodulseite';
$string['page-mod-dataform-view-index'] = 'Dataform-Aktivitätsansichten-Indexseite';
$string['page-mod-dataform-field-index'] = 'Dataform-Aktivitätsfelder-Indexseite';
$string['page-mod-dataform-access-index'] = 'Dataform-Aktivität Zugriffsregeln Indexseite';
$string['page-mod-dataform-notification-index'] = 'Dataform-Aktivität Benachrichtigungsregeln Indexseite';
$string['pagesize'] = 'Einträge pro Seite';
$string['pagingbar'] = 'Paging bar';
$string['pluginadministration'] = 'Dataform-Aktivität Verwaltung';
$string['pluginname'] = 'Dataform';
$string['random'] = 'Zufällig';
$string['range'] = 'Bereich';
$string['reference'] = 'Referenz';
$string['renewactivity'] = 'Aktivität erneuern';
$string['renewconfirm'] = 'Sie sind dabei, diese Aktivität vollständig zurückzusetzen. Die gesamte Aktivitätsstruktur und die Benutzerdaten werden gelöscht. Möchten Sie fortfahren?';
$string['deleteactivity'] = 'Aktivität löschen';
$string['requiredall'] = 'alles erforderlich';
$string['requirednotall'] = 'nicht alles erforderlich';
$string['resetsettings'] = 'Filter zurücksetzen';
$string['returntoimport'] = 'Zum Import zurückkehren';

$string['autor'] = 'Autor';
$string['email'] = 'E-Mail';

$string['save'] = 'Speichern';
$string['savenew'] = 'Neu speichern';
$string['savenewcont'] = 'Neu speichern und fortfahren';
$string['savecont'] = 'Speichern und fortfahren';
$string['savecontnew'] = 'Speichern und neu beginnen';
$string['cancel'] = 'Abbrechen';

$string['search'] = 'Suche';
$string['sendinratings'] = 'Meine letzten Bewertungen einsenden';
$string['separateentries'] = 'Jeder Eintrag in einer eigenen Datei';
$string['separateparticipants'] = 'Teilnehmer separieren';
$string['separateparticipants_help'] = 'Teilnehmer seperieren';
$string['Einstellungen'] = 'Einstellungen';
$string['spreadsheettype'] = 'Tabellen-Typ';
$string['einreichungeninpopup'] = 'Einreichungen in Popup';
$string['submission'] = 'Einreichung';
$string['submissionsview'] = 'Einreichungsansicht';
$string['subplugintype_dataformfield'] = 'Dataform-Feldtyp';
$string['subplugintype_dataformfield_plural'] = 'Dataform-Feldtypen';
$string['subplugintype_dataformtool'] = 'Dataform-Werkzeugtyp';
$string['subplugintype_dataformtool_plural'] = 'Dataform-Werkzeugtypen';
$string['subplugintype_dataformview'] = 'Dataform-Ansichtstyp';
$string['subplugintype_dataformview_plural'] = 'Dataform-Ansichtstypen';

$string['type'] = 'Typ';
$string['unlock'] = 'Entsperren';
$string['userpref'] = 'Benutzereinstellungen';

$string['modulesettings'] = 'Moduleinstellungen';
$string['fieldplugins'] = 'Feld-Plugins';
$string['viewplugins'] = 'Ansichts-Plugins';
$string['managefields'] = 'Feld-Plugins verwalten';
$string['manageviews'] = 'Ansichts-Plugins verwalten';
$string['availableplugins'] = 'Verfügbare Plugins';
$string['instances'] = 'Instanzen';
$string['pluginhasinstances'] = 'ACHTUNG: Dieses Plugin hat {$a} Instanzen.';
$string['configplugins'] = 'Bitte aktivieren Sie alle benötigten Plugins und ordnen Sie sie in der richtigen Reihenfolge an.';

// ERRORS.
$string['error:cannotbenegative'] = 'Wert kann nicht negativ sein';
