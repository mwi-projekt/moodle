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
 * Displays the DHBW International Office Module.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;
use mod_dhbwio\local\dataform\status_manager;
use mod_dhbwio\local\dataform\dataform_manager;
use mod_dhbwio\local\dataform\default_form_manager;

$id = required_param('id', PARAM_INT); // Course Module ID
$tab = optional_param('tab', 'universities', PARAM_ALPHA); // Active tab

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => $tab]);
$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Load necessary JS and CSS
$PAGE->requires->css('/mod/dhbwio/styles.css');

// For map view
if ($tab == 'universities' && !empty($dhbwio->enablemap)) {
	// Load Leaflet and initialize map
	dhbwio_load_leaflet_map($cm->id);
}

// Start output
echo $OUTPUT->header();

// Display module name
//echo $OUTPUT->heading(format_string($dhbwio->name));

// Display intro if set
if (!empty($dhbwio->intro)) {
	echo $OUTPUT->box(format_module_intro('dhbwio', $dhbwio, $cm->id), 'generalbox mod_introbox', 'dhbwiointro');
}

// Create tabs for navigation
$tabs = [];

// Tab for viewing partner universities - available to all users
$tabs[] = new tabobject(
	'universities',
	new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']),
	get_string('nav_universities', 'mod_dhbwio')
);

// Tab for applications - available to all users
$tabs[] = new tabobject(
	'bewerbungen',
	new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'bewerbungen']),
	get_string('nav_bewerbungen', 'mod_dhbwio')
);

// Tab for experience reports - available to all users if enabled
if (!empty($dhbwio->enablereports)) {
	$tabs[] = new tabobject(
		'reports',
		new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
		get_string('nav_reports', 'mod_dhbwio')
	);
}

// Tabs for IO staff
if (has_capability('mod/dhbwio:manageuniversities', $context)) {
	$tabs[] = new tabobject(
		'manageunis',
		new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']),
		get_string('nav_manageunis', 'mod_dhbwio')
	);
}

if (has_capability('mod/dhbwio:viewreports', $context)) {
	$tabs[] = new tabobject(
		'statistics',
		new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'statistics']),
		get_string('nav_statistics', 'mod_dhbwio')
	);
}

if (has_capability('mod/dhbwio:managetemplates', $context)) {
	$tabs[] = new tabobject(
		'emailtemplates',
		new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
		get_string('nav_emailtemplates', 'mod_dhbwio')
	);
}

if (has_capability('mod/dhbwio:manageuniversities', $context)) {
	$tabs[] = new tabobject(
		'fristen',
		new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'fristen']),
		get_string('nav_fristen', 'mod_dhbwio')
	);
}

$tabs[] = new tabobject(
	'learningagreement',
	new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']),
	get_string('nav_learningagreement', 'mod_dhbwio')
);

echo $OUTPUT->tabtree($tabs, $tab);

// Display the content based on the selected tab
switch ($tab) {
	case 'universities':
		if (!empty($dhbwio->enablemap)) {
			$renderer = $PAGE->get_renderer('mod_dhbwio');
			echo $renderer->render_university_view($dhbwio, $cm);
		} else {
			$renderer = $PAGE->get_renderer('mod_dhbwio');
			$renderer->display_universities_list($dhbwio, $cm);
		}
		break;

	case 'bewerbungen':
		// Try Generating new Dataform, if none is connected to this Activity-Instance
		try {
			$dataform = dataform_manager::get_course_dataform((int) $course->id);
		} catch (moodle_exception $e) {
			$dataid = \mod_dhbwio\local\dataform\default_form_manager::create_default_form((int) $course->id);
			$dataform = dataform_manager::get_course_dataform((int) $course->id);
		}
		$dataid   = (int) $dataform->id;
		$courseid = $course->id;

		// Apply Rule for Manager-Authority
		$canviewallapplications = has_capability('mod/dhbwio:viewallapplications', $context);

		// Define entries: Manager everything, students only theirs
		if ($canviewallapplications) {
			$entries = entry_manager::get_entries($dataid);
		} else {
			$entries = entry_manager::get_user_entries($dataid, $USER->id);
		}

		// ── Fortschrittsbalken nur für Studierende ──────────────────────────
		if (!$canviewallapplications) {
			// Aktuellen Status des Studierenden ermitteln
			$appstatuskey = null;
			if (!empty($entries)) {
				$firstentry   = reset($entries);
				$appstatrec   = status_manager::get_status((int) $firstentry->statusid);
				$appstatuskey = $appstatrec ? $appstatrec->shortname : null;
			}

			// Farbstufen je Fortschritt
			// Level 0 = keine Bewerbung (grau)
			// Level 1 = eingereicht   (hellgrün)
			// Level 2 = in_pruefung   (mittelgrün)
			// Level 3 = angenommen    (dunkelgrün) | abgelehnt (rot)
			$islevel = [
				'eingereicht' => 1,
				'in_pruefung' => 2,
				'angenommen'  => 3,
				'abgelehnt'   => 3,
			];
			$curlevel  = $appstatuskey ? ($islevel[$appstatuskey] ?? 0) : 0;
			$isreject  = ($appstatuskey === 'abgelehnt');

			// Farben je Stufe (grauer Fallback wenn kein Level)
			$stepcolors = ['#dee2e6', '#A5D6A7', '#43A047', $isreject ? '#dc3545' : '#1B5E20'];

			// Schritt-Konfiguration
			$step3label = get_string('appbar_result', 'mod_dhbwio');
			if ($appstatuskey === 'angenommen') {
				$step3label = get_string('appbar_accepted', 'mod_dhbwio');
			} elseif ($appstatuskey === 'abgelehnt') {
				$step3label = get_string('appbar_rejected', 'mod_dhbwio');
			}

			$barsteps = [
				['label' => get_string('appbar_submitted',    'mod_dhbwio'), 'level' => 1],
				['label' => get_string('appbar_under_review', 'mod_dhbwio'), 'level' => 2],
				['label' => $step3label,                                      'level' => 3],
			];

			echo '<div class="dhbwio-appbar-container mb-4">';
			echo '<div class="dhbwio-appbar-steps">';

			foreach ($barsteps as $si => $step) {
				if ($si === 0) {
					// Schritt 1: Submitted – grün sobald eingereicht
					if ($curlevel >= 1) {
						$circlass = 'dhbwio-appbar-circle-done';
						$lblclass = 'dhbwio-appbar-label-done';
						$icon     = '&#10003;';
					} else {
						$circlass = 'dhbwio-appbar-circle-pending';
						$lblclass = 'dhbwio-appbar-label-pending';
						$icon     = '1';
					}
				} elseif ($si === 1) {
					// Schritt 2: Under Review – gelb wenn aktiv, grün wenn abgeschlossen
					if ($curlevel >= 3) {
						$circlass = 'dhbwio-appbar-circle-done';
						$lblclass = 'dhbwio-appbar-label-done';
						$icon     = '&#10003;';
					} elseif ($curlevel === 2) {
						$circlass = 'dhbwio-appbar-circle-active';
						$lblclass = 'dhbwio-appbar-label-active';
						$icon     = '2';
					} else {
						$circlass = 'dhbwio-appbar-circle-pending';
						$lblclass = 'dhbwio-appbar-label-pending';
						$icon     = '2';
					}
				} else {
					// Schritt 3: Ergebnis – grün oder rot
					if ($curlevel >= 3) {
						$circlass = $isreject ? 'dhbwio-appbar-circle-reject' : 'dhbwio-appbar-circle-done';
						$lblclass = $isreject ? 'dhbwio-appbar-label-reject' : 'dhbwio-appbar-label-done';
						$icon     = $isreject ? '&#10007;' : '&#10003;';
					} else {
						$circlass = 'dhbwio-appbar-circle-pending';
						$lblclass = 'dhbwio-appbar-label-pending';
						$icon     = '3';
					}
				}

				echo '<div class="dhbwio-appbar-step">';
				echo '<div class="dhbwio-appbar-circle ' . $circlass . '">' . $icon . '</div>';
				echo '<div class="dhbwio-appbar-label ' . $lblclass . '">' . htmlspecialchars($step['label']) . '</div>';
				echo '</div>';

				// Linie nach diesem Schritt
				if ($si < count($barsteps) - 1) {
					if ($si === 0) {
						// Linie 1: grau → gelb → grün
						if ($curlevel >= 3)     $linecolor = '#43A047';
						elseif ($curlevel === 2) $linecolor = '#FFA000';
						else                     $linecolor = '#dee2e6';
					} else {
						// Linie 2: grau → grün oder rot
						if ($curlevel >= 3) $linecolor = $isreject ? '#dc3545' : '#43A047';
						else                $linecolor = '#dee2e6';
					}
					echo '<div class="dhbwio-appbar-line" style="background:' . $linecolor . '"></div>';
				}
			}

			echo '</div>'; // dhbwio-appbar-steps
			echo '</div>'; // dhbwio-appbar-container
		}
		// ────────────────────────────────────────────────────────────────────

		// Generating "Apply now"-Button
		$url = new moodle_url('/mod/dhbwio/application.php', [
			'id'     => $cm->id,
			'dataid' => $dataid,
		]);

		$matrixurl = new moodle_url('/local/zuweisungsmatrix/index.php', [
			'courseid' => $courseid
		]);

		echo html_writer::link(
			$url,
			get_string('createapplication', 'dhbwio'),
			['class' => 'btn btn-primary px-3 py-2']
		);

		if ($canviewallapplications) {
			echo html_writer::link(
				$matrixurl,
				'Zuweisungsmatrix',
				['class' => 'btn btn-primary px-3 py-2']
			);
		}

		$erstwunschfield  = field_manager::get_field_by_name($dataid, 'ERSTWUNSCH');
		$studiengangfield = field_manager::get_field_by_name($dataid, 'STUDIENGANG');
		$vornamefield     = field_manager::get_field_by_name($dataid, 'VORNAME');
		$nachnamefield    = field_manager::get_field_by_name($dataid, 'NACHNAME');
		$emailfield       = field_manager::get_field_by_name($dataid, 'EMAIL');

		if (empty($entries)) {
			echo html_writer::tag('p', get_string('no_applications', 'dhbwio'));
		} else {
			$applications = [];

			foreach ($entries as $entry) {
				$erstwunsch = '-';
				if ($erstwunschfield) {
					$erstwunsch = entry_manager::get_content_value($entry->id, (int) $erstwunschfield->id) ?? '-';
				}

				$statusrecord = status_manager::get_status((int) $entry->statusid);
				if ($statusrecord) {
					$statuskey = $statusrecord->shortname;
					$status = get_string('status_' . $statuskey, 'dhbwio');
				} else {
					$statuskey = 'unknown';
					$status = '-';
				}

				$statusclass = match ($statuskey) {
					'eingereicht' => 'status-submitted',
					'in_pruefung' => 'status-review',
					'angenommen'  => 'status-approved',
					'abgelehnt'   => 'status-rejected',
					default       => 'status-default',
				};

				if ($canviewallapplications) {
					$viewurl = new moodle_url('/mod/dhbwio/application_view.php', [
						'id'      => $cm->id,
						'dataid'  => $dataid,
						'entryid' => $entry->id,
					]);
					$reviewurl = new moodle_url('/mod/dhbwio/application_review.php', [
						'id'      => $cm->id,
						'dataid'  => $dataid,
						'entryid' => $entry->id,
					]);
					$actions = html_writer::link($viewurl, get_string('show', 'dhbwio')) . ' | ' .
						html_writer::link($reviewurl, get_string('review', 'dhbwio'));

					$vorname     = $vornamefield     ? entry_manager::get_content_value($entry->id, (int) $vornamefield->id)     : '';
					$nachname    = $nachnamefield    ? entry_manager::get_content_value($entry->id, (int) $nachnamefield->id)    : '';
					$email       = $emailfield       ? entry_manager::get_content_value($entry->id, (int) $emailfield->id)       : '';
					$studiengang = $studiengangfield ? entry_manager::get_content_value($entry->id, (int) $studiengangfield->id) : '';

					$applications[] = [
						'applicantname' => s(trim($vorname . ' ' . $nachname)),
						'email'         => s($email),
						'timecreated'   => userdate($entry->timecreated),
						'timemodified'  => userdate($entry->timemodified),
						'firstchoice'   => s($erstwunsch),
						'status'        => s($status),
						'statusclass'   => $statusclass,
						'actions'       => $actions,
						'studyprogram'  => s($studiengang),
					];
				} else {
					$viewurl = new moodle_url('/mod/dhbwio/application.php', [
						'id'      => $cm->id,
						'dataid'  => $dataid,
						'entryid' => $entry->id,
					]);
					$actions = html_writer::link($viewurl, get_string('show/edit', 'dhbwio'));

					if (status_manager::is_accepted((int) $entry->statusid)) {
						$laurl = new moodle_url('/mod/dhbwio/learning_agreement.php', [
							'id'      => $cm->id,
							'dataid'  => $dataid,
							'entryid' => $entry->id,
						]);
						$actions .= ' | ' . html_writer::link($laurl, get_string('learning_agreement', 'dhbwio'));
					}

					$applications[] = [
						'timecreated'  => userdate($entry->timecreated),
						'timemodified' => userdate($entry->timemodified),
						'firstchoice'  => s($erstwunsch),
						'status'       => s($status),
						'statusclass'  => $statusclass,
						'actions'      => $actions,
					];
				}
			}

			$templatecontext = [
				'title'           => $canviewallapplications
					? get_string('all_application_overview_title', 'dhbwio')
					: get_string('application_overview_title', 'dhbwio'),
				'isadmin'         => $canviewallapplications,
				'hasapplications' => !empty($applications),
				'applications'    => $applications,
				'emptytext'       => $canviewallapplications
					? get_string('all_emptytext', 'dhbwio')
					: get_string('emptytext', 'dhbwio'),
			];

			echo $OUTPUT->render_from_template('mod_dhbwio/application_overview', $templatecontext);
		}
		break;

	case 'reports':
		if (empty($dhbwio->enablereports)) {
			// Reports are disabled, redirect to main view
			redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]));
		}

		// Display reports
		if (!empty($dhbwio->enablereports)) {
			// Show add report button if student has permission
			if (has_capability('mod/dhbwio:submitreport', $context)) {
				$addurl = new moodle_url('/mod/dhbwio/report.php', [
					'cmid' => $cm->id,
					'action' => 'add'
				]);

				echo '<div class="dhbwio-actions mb-4">';
				echo '<a href="' . $addurl . '" class="btn btn-primary">';
				echo get_string('add_report', 'mod_dhbwio') . '</a>';
				echo '</div>';
			}

			// Get all reports
			$reports = $DB->get_records('dhbwio_experience_reports', [
				'dhbwio' => $dhbwio->id,
				'visible' => 1
			], 'timecreated DESC');

			if (empty($reports)) {
				echo $OUTPUT->notification(get_string('no_reports', 'mod_dhbwio'), 'info');
			} else {
				echo $OUTPUT->box_start('generalbox');

				foreach ($reports as $report) {
					// Get university and student details
					$university = $DB->get_record('dhbwio_universities', ['id' => $report->university_id]);
					$student = $DB->get_record('user', ['id' => $report->userid]);

					if (!$university || !$student) {
						continue;
					}

					// Get country name
					$countries = get_string_manager()->get_list_of_countries();
					$countryName = isset($countries[$university->country]) ? $countries[$university->country] : $university->country;

					echo '<div class="card mb-4">';
					echo '<div class="card-header">';
					echo '<h3>' . format_string($report->title) . '</h3>';

					// Display university and author
					echo '<p>';

					// Link to university detail page
					$universityurl = new moodle_url('/mod/dhbwio/university.php', [
						'cmid' => $cm->id,
						'university' => $university->id
					]);

					echo '<a href="' . $universityurl . '">' . format_string($university->name) . '</a>';
					echo ' (' . $countryName . ')';
					echo ' | ' . get_string('by', 'mod_dhbwio') . ' ' . fullname($student);
					echo ' | ' . userdate($report->timecreated);

					// Display rating if any
					if (!empty($report->rating)) {
						echo ' | ' . get_string('rating', 'mod_dhbwio') . ': ';
						for ($i = 1; $i <= 5; $i++) {
							if ($i <= $report->rating) {
								echo '★';
							} else {
								echo '☆';
							}
						}
					}

					echo '</p>';
					echo '</div>'; // End card-header

					echo '<div class="card-body">';
					echo format_text($report->content, $report->contentformat);

					// Show edit/delete actions if user is the author
					if ($report->userid == $USER->id || has_capability('mod/dhbwio:manageuniversities', $context)) {
						echo '<div class="dhbwio-actions mt-3">';

						$editurl = new moodle_url('/mod/dhbwio/report.php', [
							'cmid' => $cm->id,
							'action' => 'edit',
							'report' => $report->id
						]);

						$deleteurl = new moodle_url('/mod/dhbwio/report.php', [
							'cmid' => $cm->id,
							'action' => 'delete',
							'report' => $report->id,
							'sesskey' => sesskey()
						]);

						echo '<a href="' . $editurl . '" class="btn btn-secondary btn-sm">';
						echo get_string('edit', 'mod_dhbwio') . '</a> ';

						echo '<a href="' . $deleteurl . '" class="btn btn-danger btn-sm" onclick="return confirm(\'' .
							get_string('delete_report_confirm', 'mod_dhbwio') . '\')">';
						echo get_string('delete', 'mod_dhbwio') . '</a>';

						echo '</div>';
					}

					echo '</div>'; // End card-body
					echo '</div>'; // End card
				}

				echo $OUTPUT->box_end();
			}
		}
		break;

	case 'manageunis':
		// Check capability
		require_capability('mod/dhbwio:manageuniversities', $context);

		// Display management interface
		echo '<div class="dhbwio-manageuniversities">';

		// Add university button
		$addurl = new moodle_url('/mod/dhbwio/university.php', [
			'cmid' => $cm->id,
			'action' => 'add'
		]);

		echo '<div class="dhbwio-actions mb-4">';
		echo '<a href="' . $addurl . '" class="btn btn-primary">';
		echo get_string('add_university', 'mod_dhbwio') . '</a>';
		echo '</div>';

		// Get all universities
		$universities = $DB->get_records('dhbwio_universities', [
			'dhbwio' => $dhbwio->id
		], 'country, name');

		if (empty($universities)) {
			echo $OUTPUT->notification(get_string('no_universities', 'mod_dhbwio'), 'info');
		} else {
			$countries = get_string_manager()->get_list_of_countries();

			echo $OUTPUT->box_start('generalbox');

			$table = new html_table();
			$table->head = [
				get_string('university_name', 'mod_dhbwio'),
				get_string('university_country', 'mod_dhbwio'),
				get_string('university_city', 'mod_dhbwio'),
				get_string('university_available_slots', 'mod_dhbwio'),
				get_string('status', 'mod_dhbwio'),
				get_string('actions', 'mod_dhbwio')
			];
			$table->attributes['class'] = 'table table-striped table-hover';

			foreach ($universities as $university) {
				// Get country name
				$countryCode = $university->country;
				$countryName = isset($countries[$countryCode]) ? $countries[$countryCode] : $countryCode;

				// Create action URLs
				$editurl = new moodle_url('/mod/dhbwio/university.php', [
					'cmid' => $cm->id,
					'action' => 'edit',
					'university' => $university->id
				]);

				$deleteurl = new moodle_url('/mod/dhbwio/university.php', [
					'cmid' => $cm->id,
					'action' => 'delete',
					'university' => $university->id,
					'sesskey' => sesskey()
				]);

				$viewurl = new moodle_url('/mod/dhbwio/university.php', [
					'cmid' => $cm->id,
					'university' => $university->id
				]);

				$actions = [];

				// Edit action
				$actions[] = html_writer::link(
					$editurl,
					$OUTPUT->pix_icon('t/edit', get_string('edit')),
					['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('edit')]
				);

				// View action
				$actions[] = html_writer::link(
					$viewurl,
					$OUTPUT->pix_icon('i/preview', get_string('view')),
					['class' => 'btn btn-sm btn-outline-info', 'title' => get_string('view')]
				);

				// Delete action
				$actions[] = html_writer::link(
					$deleteurl,
					$OUTPUT->pix_icon('t/delete', get_string('delete')),
					[
						'class' => 'btn btn-sm btn-outline-danger',
						'title' => get_string('delete'),
						'onclick' => 'return confirm("' . get_string('delete_university_confirm', 'mod_dhbwio') . '")'
					]
				);

				// Active status display using badges like in email templates
				$activestatus = $university->active ?
					'<span class="badge badge-success">' . get_string('active', 'mod_dhbwio') . '</span>' :
					'<span class="badge badge-secondary">' . get_string('inactive', 'mod_dhbwio') . '</span>';

				// Add table row
				$table->data[] = [
					format_string($university->name),
					$countryName,
					format_string($university->city),
					$university->available_slots,
					$activestatus,
					'<div class="btn-group" role="group">' . implode('', $actions) . '</div>'
				];
			}

			echo html_writer::table($table);
			echo $OUTPUT->box_end();
		}

		echo '</div>'; // End dhbwio-manageuniversities
		break;

	case 'statistics':
		// Check capability
		require_capability('mod/dhbwio:viewreports', $context);
		echo $OUTPUT->notification("Statistics feature is coming soon", 'info');
		break;

	case 'fristen':
		require_capability('mod/dhbwio:manageuniversities', $context);

		echo '<div class="dhbwio-fristen">';

		// "Frist anlegen" button — nur für Manager
		if (has_capability('mod/dhbwio:manageuniversities', $context)) {
			$addurl = new moodle_url('/mod/dhbwio/frist.php', ['cmid' => $cm->id, 'action' => 'add']);
			echo '<div class="dhbwio-actions mb-4">';
			echo '<a href="' . $addurl . '" class="btn btn-primary">'
				. get_string('frist_add', 'mod_dhbwio') . '</a>';
			echo '</div>';
		}

		$fristen = $DB->get_records('dhbwio_fristen', ['dhbwio' => $dhbwio->id], 'jahrgang DESC, art');

		if (empty($fristen)) {
			echo $OUTPUT->notification(get_string('no_fristen', 'mod_dhbwio'), 'info');
		} else {
			$artlabels = [
				'stipendium'         => get_string('frist_art_stipendium', 'mod_dhbwio'),
				'bewerbung'          => get_string('frist_art_bewerbung', 'mod_dhbwio'),
				'learning_agreement' => get_string('frist_art_learning_agreement', 'mod_dhbwio'),
			];

			echo $OUTPUT->box_start('generalbox');
			$table = new html_table();
			$table->head = [
				get_string('frist_art', 'mod_dhbwio'),
				get_string('frist_studiengang', 'mod_dhbwio'),
				get_string('frist_jahrgang', 'mod_dhbwio'),
				get_string('frist_deadline', 'mod_dhbwio'),
				get_string('frist_kommentar', 'mod_dhbwio'),
				get_string('actions', 'mod_dhbwio'),
			];
			$table->attributes['class'] = 'table table-striped table-hover';

			foreach ($fristen as $frist) {
				$artlabel = $artlabels[$frist->art] ?? $frist->art;
				$sgLabel  = $frist->studiengang === 'alle'
					? get_string('frist_alle_studiengaenge', 'mod_dhbwio')
					: format_string($frist->studiengang);

				$editurl = new moodle_url('/mod/dhbwio/frist.php', [
					'cmid'    => $cm->id,
					'action'  => 'edit',
					'fristid' => $frist->id,
				]);
				$deleteurl = new moodle_url('/mod/dhbwio/frist.php', [
					'cmid'    => $cm->id,
					'action'  => 'delete',
					'fristid' => $frist->id,
					'sesskey' => sesskey(),
				]);

				$actions = [
					html_writer::link($editurl,
						$OUTPUT->pix_icon('t/edit', get_string('edit')),
						['class' => 'btn btn-sm btn-outline-secondary']),
					html_writer::link($deleteurl,
						$OUTPUT->pix_icon('t/delete', get_string('delete')),
						[
							'class'   => 'btn btn-sm btn-outline-danger',
							'onclick' => 'return confirm("' . get_string('frist_delete_confirm', 'mod_dhbwio') . '")',
						]),
				];

				$table->data[] = [
					$artlabel,
					$sgLabel,
					$frist->jahrgang,
					!empty($frist->deadline) ? userdate($frist->deadline, get_string('strftimedate', 'langconfig')) : '—',
					!empty($frist->kommentar) ? format_string($frist->kommentar) : '—',
					'<div class="btn-group" role="group">' . implode('', $actions) . '</div>',
				];
			}

			echo html_writer::table($table);
			echo $OUTPUT->box_end();
		}

		echo '</div>';
		break;

	case 'emailtemplates':
		// Check capability
		require_capability('mod/dhbwio:managetemplates', $context);

		// Display email template management interface
		echo '<div class="dhbwio-emailtemplates">';

		// Get all templates for this instance
		$templates = $DB->get_records('dhbwio_email_templates', ['dhbwio' => $dhbwio->id], 'type, lang, name');

		if (empty($templates)) {
			echo $OUTPUT->notification(get_string('no_templates', 'mod_dhbwio'), 'info');

			// Show info about creating default templates
			echo '<div class="alert alert-info">';
			echo '<h5>' . get_string('create_default_templates', 'mod_dhbwio') . '</h5>';
			echo '<p>' . get_string('create_default_templates_desc', 'mod_dhbwio') . '</p>';

			$createdefaulturl = new moodle_url('/mod/dhbwio/email_template.php', [
				'cmid' => $cm->id,
				'action' => 'createdefaults',
				'sesskey' => sesskey()
			]);
			echo '<a href="' . $createdefaulturl . '" class="btn btn-secondary">';
			echo get_string('create_default_templates_button', 'mod_dhbwio') . '</a>';
			echo '</div>';
		} else {
			// Template types for display
			$templatetypes = [
				'application_received' => get_string('template_application_received', 'mod_dhbwio'),
				'application_approved' => get_string('template_application_accepted', 'mod_dhbwio'),
				'application_rejected' => get_string('template_application_rejected', 'mod_dhbwio'),
				'application_inquiry' => get_string('template_application_inquiry', 'mod_dhbwio')
			];

			echo $OUTPUT->box_start('generalbox');

			// Create single table for all templates
			$table = new html_table();
			$table->head = [
				get_string('template_name', 'mod_dhbwio'),
				get_string('template_type', 'mod_dhbwio'),
				get_string('language'),
				get_string('template_subject', 'mod_dhbwio'),
				get_string('status', 'mod_dhbwio'),
				get_string('actions', 'mod_dhbwio')
			];
			$table->attributes['class'] = 'table table-striped table-hover';

			$stringmanager = get_string_manager();
			$languageList = $stringmanager->get_list_of_languages();

			foreach ($templates as $template) {
				$langdisplay = isset($languageList[$template->lang]) ? $languageList[$template->lang] : $template->lang;
				$typedisplay = isset($templatetypes[$template->type]) ? $templatetypes[$template->type] : $template->type;

				// Status display
				$status = $template->enabled ?
					'<span class="badge badge-success">' . get_string('enabled', 'mod_dhbwio') . '</span>' :
					'<span class="badge badge-secondary">' . get_string('disabled', 'mod_dhbwio') . '</span>';

				// Action links
				$actions = [];

				// Edit
				$editurl = new moodle_url('/mod/dhbwio/email_template.php', [
					'cmid' => $cm->id,
					'action' => 'edit',
					'template' => $template->id
				]);
				$actions[] = html_writer::link(
					$editurl,
					$OUTPUT->pix_icon('t/edit', get_string('edit')),
					['class' => 'btn btn-sm btn-outline-secondary']
				);

				// Preview
				$previewurl = new moodle_url('/mod/dhbwio/email_template.php', [
					'cmid' => $cm->id,
					'action' => 'preview',
					'template' => $template->id
				]);
				$actions[] = html_writer::link(
					$previewurl,
					$OUTPUT->pix_icon('i/preview', get_string('preview')),
					['class' => 'btn btn-sm btn-outline-info']
				);

				// Test email
				$testurl = new moodle_url('/mod/dhbwio/email_template.php', [
					'cmid' => $cm->id,
					'action' => 'test',
					'template' => $template->id,
					'sesskey' => sesskey()
				]);
				$actions[] = html_writer::link(
					$testurl,
					$OUTPUT->pix_icon('t/email', get_string('send_test_email', 'mod_dhbwio')),
					['class' => 'btn btn-sm btn-outline-warning']
				);

				// Truncate subject for display
				$subjectdisplay = strlen($template->subject) > 50 ?
					substr($template->subject, 0, 50) . '...' :
					$template->subject;

				// Add table row
				$table->data[] = [
					format_string($template->name),
					$typedisplay,
					$langdisplay,
					format_string($subjectdisplay),
					$status,
					'<div class="btn-group" role="group">' . implode('', $actions) . '</div>'
				];
			}

			echo html_writer::table($table);
			echo $OUTPUT->box_end();
		}

		echo '</div>'; // End dhbwio-emailtemplates
		break;

	default:
		// Default to universities view
		$renderer = $PAGE->get_renderer('mod_dhbwio');
		$renderer->display_universities_list($dhbwio, $cm);
		break;

	case 'learningagreement':
		$iscoordinator = has_capability('mod/dhbwio:manageuniversities', $context);

		echo '<div class="dhbwio-learningagreement">';
		echo '<h3>' . get_string('nav_learningagreement', 'mod_dhbwio') . '</h3>';

		if (!$iscoordinator) {
			// Student view: upload form + own uploaded files
			$myrecord = $DB->get_record('dhbwio_learning_agreements', ['dhbwio' => $dhbwio->id, 'userid' => $USER->id]);
			$uploadurl = new moodle_url('/mod/dhbwio/learning_agreement.php', ['cmid' => $cm->id]);

			if ($myrecord) {
				$statuslabels = [
					'pending'  => get_string('la_status_pending', 'mod_dhbwio'),
					'approved' => get_string('la_status_approved', 'mod_dhbwio'),
					'rejected' => get_string('la_status_rejected', 'mod_dhbwio'),
				];
				$statuscolors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
				$statuskey = $myrecord->status ?? 'pending';

				echo '<div class="alert alert-' . ($statuscolors[$statuskey] ?? 'secondary') . '">';
				echo get_string('la_current_status', 'mod_dhbwio') . ': <strong>'
					. ($statuslabels[$statuskey] ?? $statuskey) . '</strong>';
				if (!empty($myrecord->comment)) {
					echo '<br><em>' . s($myrecord->comment) . '</em>';
				}
				echo '</div>';

				// Show the uploaded file
				$fs = get_file_storage();
				$files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $myrecord->id, '', false);
				if (!empty($files)) {
					$file = reset($files);
					$fileurl = moodle_url::make_pluginfile_url(
						$context->id, 'mod_dhbwio', 'learning_agreements', $myrecord->id,
						$file->get_filepath(), $file->get_filename()
					);
					echo '<p>' . get_string('la_uploaded_file', 'mod_dhbwio') . ': ';
					echo '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a></p>';
				}

				// Allow re-upload if rejected
				if ($statuskey === 'rejected' || $statuskey === 'pending') {
					echo '<button class="btn btn-secondary" disabled>'
						. get_string('la_create_btn', 'mod_dhbwio') . '</button> ';
					echo '<a href="' . $uploadurl . '" class="btn btn-primary">'
						. get_string('la_reupload', 'mod_dhbwio') . '</a>';
				}
			} else {
				echo '<p>' . get_string('la_no_upload_yet', 'mod_dhbwio') . '</p>';
				echo '<button class="btn btn-secondary" disabled>'
					. get_string('la_create_btn', 'mod_dhbwio') . '</button> ';
				echo '<a href="' . $uploadurl . '" class="btn btn-primary">'
					. get_string('la_upload_btn', 'mod_dhbwio') . '</a>';
			}
		} else {
			// Coordinator view: all uploaded LAs
			$records = $DB->get_records('dhbwio_learning_agreements', ['dhbwio' => $dhbwio->id], 'timecreated DESC');

			if (empty($records)) {
				echo $OUTPUT->notification(get_string('la_no_submissions', 'mod_dhbwio'), 'info');
			} else {
				$statuslabels = [
					'pending'  => get_string('la_status_pending', 'mod_dhbwio'),
					'approved' => get_string('la_status_approved', 'mod_dhbwio'),
					'rejected' => get_string('la_status_rejected', 'mod_dhbwio'),
				];
				$fs = get_file_storage();

				$table = new html_table();
				$table->head = [
					get_string('la_col_student', 'mod_dhbwio'),
					get_string('la_col_file', 'mod_dhbwio'),
					get_string('la_col_submitted', 'mod_dhbwio'),
					get_string('la_col_status', 'mod_dhbwio'),
					get_string('actions', 'mod_dhbwio'),
				];
				$table->attributes['class'] = 'table table-striped table-hover';

				foreach ($records as $rec) {
					$student = $DB->get_record('user', ['id' => $rec->userid]);
					$studentname = $student ? fullname($student) : '(unbekannt)';

					$files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $rec->id, '', false);
					if (!empty($files)) {
						$file = reset($files);
						$fileurl = moodle_url::make_pluginfile_url(
							$context->id, 'mod_dhbwio', 'learning_agreements', $rec->id,
							$file->get_filepath(), $file->get_filename()
						);
						$filelink = '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a>';
					} else {
						$filelink = '-';
					}

					$reviewurl = new moodle_url('/mod/dhbwio/learning_agreement.php', [
						'cmid'   => $cm->id,
						'action' => 'review',
						'laid'   => $rec->id,
					]);

					$statusbadge = '<span class="badge badge-'
						. ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$rec->status ?? 'pending']
						. '">' . ($statuslabels[$rec->status ?? 'pending'] ?? $rec->status) . '</span>';

					$table->data[] = [
						$studentname,
						$filelink,
						userdate($rec->timecreated),
						$statusbadge,
						'<a href="' . $reviewurl . '" class="btn btn-sm btn-secondary">'
							. get_string('la_review_btn', 'mod_dhbwio') . '</a>',
					];
				}

				echo html_writer::table($table);
			}
		}

		echo '</div>';
		break;
}

echo $OUTPUT->footer();
