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
 * German strings for DHBW International Office.
 *
 * @package     mod_dhbwio
 * @copyright   2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Allgemeine Strings
$string['pluginname'] = 'International Office';
$string['modulename'] = 'International Office';
$string['modulenameplural'] = 'International Office';
$string['dhbwioname'] = 'Name';
$string['dhbwioname_help'] = 'Name der International Office-Instanz';
$string['dhbwio'] = 'dhbwio';
$string['pluginadministration'] = 'International Office Administration';
$string['dhbwiosettings'] = 'International Office Einstellungen';
$string['enable_map_view'] = 'Kartenansicht aktivieren';
$string['enable_map_view_desc'] = 'Hochschulen auf einer interaktiven Weltkarte anzeigen';
$string['enable_reports'] = 'Erfahrungsberichte aktivieren';
$string['enable_reports_desc'] = 'Studierenden erlauben, Erfahrungsberichte einzureichen';
$string['general_settings'] = 'Einstellungen';
$string['general_settings_desc'] = 'Allgemeine Einstellungen für das International Office-Plugin konfigurieren.';
$string['enable_email_notifications'] = 'Automatische E-Mail-Benachrichtigungen';
$string['enable_email_notifications_desc'] = 'E-Mail-Benachrichtigungen an Studierende senden, wenn ihre Bewerbungen aktualisiert werden';
$string['geocoding_settings'] = 'Geocoding-Einstellungen';
$string['geocoding_settings_desc'] = 'Einstellungen für die Adressgeokodierungsfunktion konfigurieren.';
$string['geocoding_provider'] = 'Geocoding-Anbieter';
$string['geocoding_provider_desc'] = 'Wählen Sie aus, welcher Dienst für die Umwandlung von Adressen in Koordinaten verwendet werden soll. Hinweis: Einige Anbieter benötigen einen API-Schlüssel.';
$string['geocoding_api_key'] = 'Geocoding-API-Schlüssel';
$string['geocoding_api_key_desc'] = 'API-Schlüssel für den ausgewählten Geocoding-Anbieter (erforderlich für Google Maps und Mapbox).';

// Adresse und Geocoding bezogene Strings
$string['address'] = 'Straße und Hausnummer';
$string['postal_code'] = 'Postleitzahl';
$string['get_coordinates'] = 'Koordinaten abrufen';
$string['get_coordinates_help'] = 'Breitengrad- und Längengrad-Koordinaten automatisch anhand der Adressinformationen abrufen.';

// Geocoding-Statusmeldungen
$string['geocoding_in_progress'] = 'Koordinaten werden gesucht...';
$string['geocoding_success'] = 'Koordinaten erfolgreich gefunden!';
$string['geocoding_error'] = 'Beim Abrufen der Koordinaten ist ein Fehler aufgetreten.';
$string['geocoding_no_results'] = 'Keine Koordinaten für diese Adresse gefunden.';
$string['geocoding_missing_fields'] = 'Stadt und Land sind für die Geocodierung erforderlich.';
$string['geocoding_missing_api_key'] = 'API-Schlüssel ist für diesen Geocoding-Anbieter erforderlich.';
$string['geocoding_api_error'] = 'Geocoding-API-Fehler: {$a}';

// Berechtigungen
$string['dhbwio:addinstance'] = 'Neue International Office-Instanz hinzufügen';
$string['dhbwio:view'] = 'International Office-Inhalte anzeigen';
$string['dhbwio:manageuniversities'] = 'Partnerhochschulen verwalten';
$string['dhbwio:submitreport'] = 'Erfahrungsbericht einreichen';
$string['dhbwio:managetemplates'] = 'E-Mail-Vorlagen verwalten';
$string['dhbwio:viewreports'] = 'Berichte und Statistiken anzeigen';

// Navigation
$string['nav_universities'] = 'Partnerhochschulen';
$string['nav_manageunis'] = 'Hochschulen verwalten';
$string['nav_reports'] = 'Erfahrungsberichte';
$string['nav_statistics'] = 'Statistiken';
$string['nav_emailtemplates'] = 'E-Mail-Vorlagen';
$string['nav_applications'] = 'Bewerbungen';
$string['nav_myapplications'] = 'Meine Bewerbungen';

// Hochschulen
$string['university_name'] = 'Name der Hochschule';
$string['university_country'] = 'Land';
$string['country'] = 'Land';
$string['country_help'] = 'Wählen Sie das Land aus, in dem sich die Hochschule befindet.';
$string['university_city'] = 'Stadt';
$string['university_website'] = 'Webseite';
$string['university_description'] = 'Beschreibung';
$string['university_location'] = 'Standort';
$string['university_coordinates'] = 'Koordinaten';
$string['university_available_slots'] = 'Verfügbare Plätze';
$string['university_requirements'] = 'Anforderungen';
$string['university_active'] = 'Aktiv';
$string['university_active_desc'] = 'Hochschule für Studierende sichtbar machen';
$string['university_image'] = 'Hochschulbild';
$string['university_image_help'] = 'Ein repräsentatives Bild der Hochschule';
$string['university_details'] = 'Hochschuldetails';
$string['add_university'] = 'Hochschule hinzufügen';
$string['edit_university'] = 'Hochschule bearbeiten';
$string['delete_university'] = 'Hochschule löschen';
$string['delete_university_confirm'] = 'Sind Sie sicher, dass Sie diese Hochschule löschen möchten?';
$string['university_saved'] = 'Hochschule erfolgreich gespeichert';
$string['university_deleted'] = 'Hochschule erfolgreich gelöscht';
$string['no_universities'] = 'Keine Hochschulen verfügbar';
$string['view_details'] = 'Details anzeigen';
$string['back_to_universities'] = 'Zurück zur Hochschulliste';
$string['basic_information'] = 'Grundinformationen';
$string['capacity_info'] = 'Kapazitätsinformationen';
$string['status'] = 'Status';
$string['latitude'] = 'Breitengrad';
$string['latitude_help'] = 'Geografischer Breitengrad der Hochschule (zwischen -90 und 90)';
$string['longitude'] = 'Längengrad';
$string['longitude_help'] = 'Geografischer Längengrad der Hochschule (zwischen -180 und 180)';
$string['invalid_latitude'] = 'Ungültiger Breitengrad. Der Wert muss zwischen -90 und 90 liegen.';
$string['invalid_longitude'] = 'Ungültiger Längengrad. Der Wert muss zwischen -180 und 180 liegen.';
$string['invalid_slots'] = 'Ungültige Anzahl von Plätzen. Der Wert muss positiv sein.';

// Semesterzeiträume
$string['semester_start_month'] = 'Semesterbeginn (Monat)';
$string['semester_start_month_help'] = 'Der Monat, in dem das Semester typischerweise beginnt';
$string['semester_end_month'] = 'Semesterende (Monat)';
$string['semester_end_month_help'] = 'Der Monat, in dem das Semester typischerweise endet';
$string['semester_period'] = 'Semesterzeitraum';

// Gebühren
$string['semester_fees'] = 'Semestergebühren';
$string['semester_fees_help'] = 'Der Betrag, den Studierende pro Semester zahlen müssen';
$string['fee_currency'] = 'Währung';
$string['currency_eur'] = 'Euro (€)';
$string['currency_usd'] = 'US-Dollar ($)';
$string['currency_gbp'] = 'Britisches Pfund (£)';
$string['currency_chf'] = 'Schweizer Franken (CHF)';
$string['currency_aud'] = 'Australischer Dollar (A$)';
$string['currency_cad'] = 'Kanadischer Dollar (C$)';
$string['currency_jpy'] = 'Japanischer Yen (¥)';
$string['currency_cny'] = 'Chinesischer Yuan (¥)';
$string['currency_sek'] = 'Schwedische Krone (SEK)';
$string['currency_nok'] = 'Norwegische Krone (NOK)';
$string['currency_dkk'] = 'Dänische Krone (DKK)';
$string['currency_other'] = 'Andere Währung';
$string['invalid_fees'] = 'Ungültiger Gebührenbetrag. Der Wert muss positiv sein.';

// Unterkunft
$string['accommodation_type'] = 'Unterkunftsart';
$string['accommodation_type_help'] = 'Die Art der Unterkunft, die für Austauschstudierende verfügbar ist';
$string['accommodation_dorm'] = 'Wohnheim/Studentenwohnheim';
$string['accommodation_apartment'] = 'Universitätsapartments';
$string['accommodation_homestay'] = 'Gastfamilien';
$string['accommodation_hotel'] = 'Hotel/Hostel';
$string['accommodation_airbnb'] = 'Airbnb/Kurzzeitmiete';
$string['accommodation_private'] = 'Privatvermietungen';
$string['accommodation_various'] = 'Verschiedene Optionen verfügbar';
$string['accommodation_none'] = 'Keine Unterkunft vorhanden';

// Erfahrungsberichte
$string['report_title'] = 'Berichtstitel';
$string['report_content'] = 'Ihre Erfahrung';
$string['report_rating'] = 'Bewertung (1-5)';
$string['report_rating_help'] = '1 = schlecht, 5 = ausgezeichnet';
$string['report_submit'] = 'Bericht einreichen';
$string['report_submitted'] = 'Bericht erfolgreich eingereicht';
$string['report_edit'] = 'Bericht bearbeiten';
$string['report_delete'] = 'Bericht löschen';
$string['report_delete_confirm'] = 'Sind Sie sicher, dass Sie diesen Bericht löschen möchten?';
$string['report_visible'] = 'Für Studierende sichtbar';
$string['report_visible_desc'] = 'Bericht für andere Studierende sichtbar machen';
$string['no_reports'] = 'Noch keine Erfahrungsberichte verfügbar';
$string['no_reports_for_university'] = 'Für diese Hochschule sind noch keine Erfahrungsberichte verfügbar';
$string['add_report'] = 'Erfahrungsbericht hinzufügen';
$string['attachments'] = 'Anhänge';
$string['attachments_help'] = 'Sie können Ihrem Erfahrungsbericht zusätzliche Dateien hinzufügen';
$string['select_rating'] = 'Bewertung auswählen';
$string['rating_poor'] = 'Schlecht';
$string['rating_fair'] = 'Ausreichend';
$string['rating_good'] = 'Gut';
$string['rating_very_good'] = 'Sehr gut';
$string['rating_excellent'] = 'Ausgezeichnet';
$string['university'] = 'Hochschule';
$string['edit'] = 'Bearbeiten';
$string['delete'] = 'Löschen';
$string['by'] = 'von';
$string['rating'] = 'Bewertung';
$string['reports'] = 'Berichte';
$string['report_semester'] = 'Semester';
$string['report_course'] = 'Studiengang';
$string['report_attachment'] = 'Anhang';

// Visualisierung
$string['map_view'] = 'Kartenansicht';
$string['list_view'] = 'Listenansicht';
$string['all'] = 'Alle';
$string['filter'] = 'Filter';
$string['reset'] = 'Zurücksetzen';
$string['actions'] = 'Aktionen';
$string['statistics'] = 'Statistiken';
$string['kpi_dashboard'] = 'KPI-Dashboard';
$string['capacity_usage'] = 'Kapazitätsauslastung';
$string['application_trends'] = 'Bewerbungstrends';
$string['popular_universities'] = 'Beliebte Hochschulen';
$string['student_distribution'] = 'Studierendenverteilung';
$string['semester_distribution'] = 'Semesterverteilung';
$string['country_distribution'] = 'Länderverteilung';

// E-Mail-Vorlagen
$string['email_templates'] = 'E-Mail-Vorlagen';
$string['template_name'] = 'Vorlagenname';
$string['template_subject'] = 'E-Mail-Betreff';
$string['template_body'] = 'E-Mail-Text';
$string['template_type'] = 'Vorlagentyp';
$string['template_variables'] = 'Verfügbare Variablen';
$string['template_variables_help'] = 'Diese Variablen werden beim Versenden der E-Mail durch tatsächliche Daten ersetzt';
$string['add_template'] = 'Vorlage hinzufügen';
$string['edit_template'] = 'Vorlage bearbeiten';
$string['delete_template'] = 'Vorlage löschen';
$string['delete_template_confirm'] = 'Sind Sie sicher, dass Sie diese Vorlage löschen möchten?';
$string['template_saved'] = 'Vorlage erfolgreich gespeichert';
$string['template_deleted'] = 'Vorlage erfolgreich gelöscht';
$string['template_application_received'] = 'Bewerbung eingegangen';
$string['template_application_accepted'] = 'Bewerbung angenommen';
$string['template_application_rejected'] = 'Bewerbung abgelehnt';
$string['template_application_inquiry'] = 'Bewerbungsanfrage';
$string['variable_student_name'] = 'Vollständiger Name des Studierenden';
$string['variable_student_firstname'] = 'Vorname des Studierenden';
$string['variable_university_name'] = 'Name der Hochschule';
$string['variable_application_date'] = 'Bewerbungsdatum';
$string['variable_semester'] = 'Beantragtes Semester';
$string['variable_status'] = 'Bewerbungsstatus';
$string['variable_comments'] = 'Kommentare';
$string['preview_template'] = 'Vorschau';
$string['send_test_email'] = 'Test-E-Mail senden';
$string['test_email_sent'] = 'Test-E-Mail erfolgreich gesendet';

// Bewerbungsprozess
$string['application'] = 'Bewerbung';
$string['applications'] = 'Bewerbungen';
$string['application_form'] = 'Bewerbungsformular';
$string['apply_now'] = 'Jetzt bewerben';
$string['application_status'] = 'Status';
$string['application_date'] = 'Bewerbungsdatum';
$string['status_draft'] = 'Entwurf';
$string['status_submitted'] = 'Eingereicht';
$string['status_under_review'] = 'In Prüfung';
$string['status_accepted'] = 'Angenommen';
$string['status_rejected'] = 'Abgelehnt';
$string['status_waitlisted'] = 'Warteliste';
$string['status_inquiry'] = 'Zusätzliche Informationen angefordert';
$string['status_withdrawn'] = 'Zurückgezogen';
$string['application_saved'] = 'Bewerbung als Entwurf gespeichert';
$string['application_submitted'] = 'Bewerbung erfolgreich eingereicht';
$string['application_updated'] = 'Bewerbung erfolgreich aktualisiert';
$string['choose_universities'] = 'Hochschulen auswählen';
$string['priority'] = 'Priorität';
$string['first_choice'] = 'Erste Wahl';
$string['second_choice'] = 'Zweite Wahl';
$string['third_choice'] = 'Dritte Wahl';
$string['personal_information'] = 'Persönliche Informationen';
$string['academic_information'] = 'Akademische Informationen';
$string['course_of_study'] = 'Studiengang';
$string['current_semester'] = 'Aktuelles Semester';
$string['gpa'] = 'Notendurchschnitt';
$string['language_skills'] = 'Sprachkenntnisse';
$string['language'] = 'Sprache';
$string['proficiency_level'] = 'Sprachniveau';
$string['beginner'] = 'Anfänger';
$string['intermediate'] = 'Mittelstufe';
$string['advanced'] = 'Fortgeschritten';
$string['fluent'] = 'Fließend';
$string['native'] = 'Muttersprache';
$string['motivation'] = 'Motivation';
$string['motivation_letter'] = 'Motivationsschreiben';
$string['motivation_letter_help'] = 'Erklären Sie, warum Sie an diesen Hochschulen studieren möchten';
$string['submit_application'] = 'Bewerbung einreichen';
$string['save_draft'] = 'Als Entwurf speichern';
$string['university_already_selected'] = 'Diese Hochschule wurde bereits ausgewählt';
$string['review_application'] = 'Bewerbung prüfen';
$string['process_application'] = 'Bewerbung bearbeiten';
$string['request_information'] = 'Zusätzliche Informationen anfordern';
$string['accept_application'] = 'Annehmen';
$string['reject_application'] = 'Ablehnen';
$string['waitlist_application'] = 'Zur Warteliste hinzufügen';
$string['add_comment'] = 'Kommentar hinzufügen';
$string['comment'] = 'Kommentar';
$string['comments'] = 'Kommentare';
$string['application_history'] = 'Bewerbungsverlauf';
$string['no_applications'] = 'Keine Bewerbungen gefunden';
$string['confirm_status_change'] = 'Sind Sie sicher, dass Sie den Status auf {$a} ändern möchten?';
$string['send_notification'] = 'Benachrichtigungs-E-Mail senden';

// Formularvalidierung
$string['required'] = 'Dieses Feld ist erforderlich';
$string['invalid_email'] = 'Ungültige E-Mail-Adresse';
$string['select_at_least_one'] = 'Bitte wählen Sie mindestens eine Option aus';
$string['file_size_exceeded'] = 'Dateigröße überschritten';
$string['invalid_file_type'] = 'Ungültiger Dateityp';

// Monatsnamen
$string['month_1'] = 'Januar';
$string['month_2'] = 'Februar';
$string['month_3'] = 'März';
$string['month_4'] = 'April';
$string['month_5'] = 'Mai';
$string['month_6'] = 'Juni';
$string['month_7'] = 'Juli';
$string['month_8'] = 'August';
$string['month_9'] = 'September';
$string['month_10'] = 'Oktober';
$string['month_11'] = 'November';
$string['month_12'] = 'Dezember';