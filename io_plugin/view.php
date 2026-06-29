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
use mod_dhbwio\local\dataform\la_manager;

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

// Tab for learning agreements - available to all users if they have accepted the app or are managers
if( has_capability('mod/dhbwio:viewalllearningagreements', $context) || la_manager::has_user_accepted_app($USER->id)) {
$tabs[] = new tabobject(
	'learningagreement',
	new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']),
	get_string('nav_learningagreements', 'mod_dhbwio')
);
}

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
	'files',
	new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'files']),
	get_string('nav_files', 'mod_dhbwio')
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

			// Level-Zuordnung je Status
			$isnachzureichen = ($appstatuskey === 'nachzureichen');
			$isreject        = ($appstatuskey === 'abgelehnt');

			if ($isnachzureichen) {
				// 4-Schritt-Modus: Eingereicht → In Prüfung → Nachzureichen → Ergebnis
				$islevel = [
					'eingereicht'    => 1,
					'in_pruefung'    => 2,
					'nachzureichen'  => 3,
					'angenommen'     => 4,
					'abgelehnt'      => 4,
				];
				$curlevel = 3; // nachzureichen ist aktiv
				$barsteps = [
					['label' => get_string('appbar_submitted',    'mod_dhbwio'), 'level' => 1],
					['label' => get_string('appbar_under_review', 'mod_dhbwio'), 'level' => 2],
					['label' => get_string('appbar_nachzureichen', 'mod_dhbwio'), 'level' => 3],
					['label' => get_string('appbar_result',       'mod_dhbwio'), 'level' => 4],
				];
			} else {
				// Standard 3-Schritt-Modus
				$islevel = [
					'eingereicht' => 1,
					'in_pruefung' => 2,
					'angenommen'  => 3,
					'abgelehnt'   => 3,
				];
				$curlevel = $appstatuskey ? ($islevel[$appstatuskey] ?? 0) : 0;

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
			}

			// ── Hinweis-Banner ────────────────────────────────────────────────
			if (!empty($entries)) {
				$firstentry = reset($entries);
				if ($isnachzureichen) {
					echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
					echo get_string('alert_nachzureichen', 'mod_dhbwio');
					echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>';
					echo '</div>';
				} elseif ($curlevel >= 3) {
					$viewurl = new moodle_url('/mod/dhbwio/application.php', [
						'id'      => $cm->id,
						'dataid'  => $dataid,
						'entryid' => $firstentry->id,
					]);
					if ($isreject) {
						$alertclass = 'alert-danger';
						$alertmsg   = get_string('alert_rejected', 'mod_dhbwio');
					} else {
						$alertclass = 'alert-success';
						$alertmsg   = get_string('alert_accepted', 'mod_dhbwio');
					}
					echo '<div class="alert ' . $alertclass . ' alert-dismissible fade show" role="alert">';
					echo $alertmsg . ' ';
					echo html_writer::link($viewurl, get_string('alert_open_application', 'mod_dhbwio'), ['class' => 'alert-link fw-bold']);
					echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>';
					echo '</div>';
				}
			}

			echo '<div class="dhbwio-appbar-container mb-4">';
			echo '<div class="dhbwio-appbar-steps">';

			$totalsteps = count($barsteps);
			foreach ($barsteps as $si => $step) {
				$steplevel = $step['level'];

				if ($isnachzureichen && $si === 2) {
					// "Nachzureichen" – aktiv (gelb/orange)
					$circlass = 'dhbwio-appbar-circle-active';
					$lblclass = 'dhbwio-appbar-label-active';
					$icon     = '3';
				} elseif ($curlevel > $steplevel) {
					$circlass = 'dhbwio-appbar-circle-done';
					$lblclass = 'dhbwio-appbar-label-done';
					$icon     = '&#10003;';
				} elseif ($curlevel === $steplevel && !$isnachzureichen) {
					if ($isreject && $si === $totalsteps - 1) {
						$circlass = 'dhbwio-appbar-circle-reject';
						$lblclass = 'dhbwio-appbar-label-reject';
						$icon     = '&#10007;';
					} elseif ($curlevel >= 3) {
						$circlass = 'dhbwio-appbar-circle-done';
						$lblclass = 'dhbwio-appbar-label-done';
						$icon     = '&#10003;';
					} else {
						$circlass = 'dhbwio-appbar-circle-active';
						$lblclass = 'dhbwio-appbar-label-active';
						$icon     = (string) $steplevel;
					}
				} else {
					$circlass = 'dhbwio-appbar-circle-pending';
					$lblclass = 'dhbwio-appbar-label-pending';
					$icon     = (string) $steplevel;
				}

				echo '<div class="dhbwio-appbar-step">';
				echo '<div class="dhbwio-appbar-circle ' . $circlass . '">' . $icon . '</div>';
				echo '<div class="dhbwio-appbar-label ' . $lblclass . '">' . htmlspecialchars($step['label']) . '</div>';
				echo '</div>';

				if ($si < $totalsteps - 1) {
					if ($curlevel > $steplevel) {
						$linecolor = ($isnachzureichen && $si === 1) ? '#FFA000' : '#43A047';
					} elseif ($isnachzureichen && $si === 1) {
						$linecolor = '#FFA000';
					} else {
						$linecolor = '#dee2e6';
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

		// Generating "Zuweisungsmatrix"-Button
		if ($canviewallapplications) {
			echo html_writer::link(
				$matrixurl,
				'Zuweisungsmatrix',
				['class' => 'btn btn-primary px-3 py-2']
			);
		}

		$erstwunschfield  = field_manager::get_field_by_name($dataid, 'ERSTWUNSCH');
		$zweitwunschfield = field_manager::get_field_by_name($dataid, 'ZWEITWUNSCH');
		$drittwunschfield = field_manager::get_field_by_name($dataid, 'DRITTWUNSCH');
		$studiengangfield = field_manager::get_field_by_name($dataid, 'STUDIENGANG');
		$vornamefield     = field_manager::get_field_by_name($dataid, 'VORNAME');
		$nachnamefield    = field_manager::get_field_by_name($dataid, 'NACHNAME');
		$emailfield       = field_manager::get_field_by_name($dataid, 'EMAIL');

		if (empty($entries)) {
			echo html_writer::tag('p', get_string('no_applications', 'dhbwio'));
		} else {
			$applications = [];

			foreach ($entries as $entry) {
				$getvalue = static function (string $fieldname) use ($dataid, $entry) {
					$field = field_manager::get_field_by_name($dataid, $fieldname);

					if (!$field) {
						return '';
					}

					return entry_manager::get_content_value($entry->id, (int)$field->id) ?? '';
				};

				$accepteduniversity = entry_manager::get_accepted_university_label(
					$entry,
					$getvalue
				);

				$firstchoice = entry_manager::format_university_choice(
					$getvalue('ERSTWUNSCH')
				);

				$secondchoice = entry_manager::format_university_choice(
					$getvalue('ZWEITWUNSCH')
				);

				$thirdchoice = entry_manager::format_university_choice(
					$getvalue('DRITTWUNSCH')
				);

				$statusrecord = status_manager::get_status((int) $entry->statusid);
				if ($statusrecord) {
					$statuskey = $statusrecord->shortname;
					$strkey    = 'status_' . $statuskey;
					$status    = get_string_manager()->string_exists($strkey, 'dhbwio')
						? get_string($strkey, 'dhbwio')
						: $statusrecord->label;
				} else {
					$statuskey = 'unknown';
					$status = '-';
				}

				$statusclass = match ($statuskey) {
					'eingereicht'   => 'status-submitted',
					'in_pruefung'   => 'status-review',
					'angenommen'    => 'status-approved',
					'abgelehnt'     => 'status-rejected',
					'nachzureichen' => 'status-review',
					default         => 'status-default',
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
					$studiengang = '';

					if ($studiengangfield) {
						$studiengangvalue = entry_manager::get_content_value(
							$entry->id,
							(int)$studiengangfield->id
						);

						if (is_numeric($studiengangvalue) && (int)$studiengangvalue > 0) {
							$studiengang = entry_manager::get_studyprogram_label((int)$studiengangvalue);
						} else {
							$studiengang = (string)$studiengangvalue;
						}
					}

					$applications[] = [
						'applicantname' => s(trim($vorname . ' ' . $nachname)),
						'email'         => s($email),
						'timecreated'   => userdate($entry->timecreated),
						'timemodified'  => userdate($entry->timemodified),
						'acceptedchoice'   => s($accepteduniversity),
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

					

					$applications[] = [
						'timecreated'  => userdate($entry->timecreated),
						'acceptedchoice'  => s($accepteduniversity),
						'firstchoice'    => s($firstchoice),
						'secondchoice'   => s($secondchoice),
						'thirdchoice'    => s($thirdchoice),
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




	case 'learningagreement':
        //Manager can view all learning agreements
        $canviewallla = has_capability('mod/dhbwio:viewalllearningagreements', $context);

        // Get the learning agreement entry for the current user or all if manager
        if ($canviewallla) {
            $laentries = la_manager::get_all_las();
        } else {
            $laentries = la_manager::get_la_by_userid_ar($USER->id);
        }

        // Fortschrittsbalken nur   für Studierende
        if (!$canviewallla) {
            $laentry = reset($laentries); // For students, we have a single entry

            //Wenn es keine Learning Agreement gibt, dann status direkt auf erstellen setzen
            if(!$laentry) {
                $laststatuskey = 'erstellen';
            } else {
                // Get the status of the learning agreement

                $laststatus = la_manager::get_la_status_by_la_id($laentry->id);
                $laststatuskey = $laststatus ? $laststatus->shortname : null;
            }

            //Statusbar mit 2 Modi -> 3 Schritte mit erstellen erstellen , in überprüfung und angenommen / abgelehnt
            // -> 4 Schritte mit erstellen, in überprüfung, überarbeitung_nötig und angenommen / abgelehnt
            if( $laststatuskey === 'ueberarbeitung_noetig') {
                $islevel = [
                    'erstellen' => 1,
                    'in_ueberpruefung' => 2,
                    'ueberarbeitung_noetig' => 3,
                    'akzeptiert' => 4,
                    'abgelehnt' => 4,
                ];
                $curlevel = 3; // ueberarbeitung_noetig ist aktiv
                $barsteps = [
                    ['label' => get_string('la_step_create', 'mod_dhbwio'), 'level' => 1],
                    ['label' => get_string('la_step_under_review', 'mod_dhbwio'), 'level' => 2],
                    ['label' => get_string('la_step_revision_needed', 'mod_dhbwio'), 'level' => 3],
                    ['label' => get_string('la_step_result', 'mod_dhbwio'), 'level' => 4],
                ];
            } else {
                $islevel = [
                    'erstellen' => 1,
                    'in_ueberpruefung' => 2,
                    'akzeptiert' => 3,
                    'abgelehnt' => 3,
                ];
                $curlevel = $laststatuskey ? ($islevel[$laststatuskey] ?? 0) : 0;

                $step3label = get_string('la_step_result', 'mod_dhbwio');
                if ($laststatuskey === 'angenommen') {
                    $step3label = get_string('la_step_accepted', 'mod_dhbwio');
                } elseif ($laststatuskey === 'abgelehnt') {
                    $step3label = get_string('la_step_rejected', 'mod_dhbwio');
                }
                $barsteps = [
                    ['label' => get_string('la_step_create', 'mod_dhbwio'), 'level' => 1],
                    ['label' => get_string('la_step_under_review', 'mod_dhbwio'), 'level' => 2],
                    ['label' => $step3label,                                      'level' => 3],
                ];
            }

            // darstellung des Fortschrittsbalkens
            echo '<div class="dhbwio-appbar-container mb-4">';
            echo '<div class="dhbwio-appbar-steps">';

            $ueberarbeitung_noetig  = ($laststatuskey === 'ueberarbeitung_noetig');
            $isreject               = ($laststatuskey === 'abgelehnt');
            $isaccepted             = ($laststatuskey === 'angenommen');
            $totalsteps = count($barsteps);
            foreach ($barsteps as $si => $step) {
                $steplevel = $step['level'];

                if( $ueberarbeitung_noetig && $si === 2) {
                    // "Überarbeitung nötig" – aktiv (gelb/orange)
                    $circlass = 'dhbwio-appbar-circle-active';
                    $lblclass = 'dhbwio-appbar-label-active';
                    $icon     = '3';
                } elseif ($curlevel > $steplevel) {
                    $circlass = 'dhbwio-appbar-circle-done';
                    $lblclass = 'dhbwio-appbar-label-done';
                    $icon     = '&#10003;';
                } elseif ($curlevel === $steplevel && !$ueberarbeitung_noetig) {
                    if ($isreject && $si === $totalsteps - 1) {
                        $circlass = 'dhbwio-appbar-circle-reject';
                        $lblclass = 'dhbwio-appbar-label-reject';
                        $icon     = '&#10007;';
                    } elseif ($curlevel >= 3) {
                        $circlass = 'dhbwio-appbar-circle-done';
                        $lblclass = 'dhbwio-appbar-label-done';
                        $icon     = '&#10003;';
                    } else {
                        $circlass = 'dhbwio-appbar-circle-active';
                        $lblclass = 'dhbwio-appbar-label-active';
                        $icon     = (string) $steplevel;
                    }
                } else {
                    $circlass = 'dhbwio-appbar-circle-pending';
                    $lblclass = 'dhbwio-appbar-label-pending';
                    $icon     = (string) $steplevel;
                }

                echo '<div class="dhbwio-appbar-step">';
                echo '<div class="dhbwio-appbar-circle ' . $circlass . '">' . $icon . '</div>';
                echo '<div class="dhbwio-appbar-label ' . $lblclass . '">' . htmlspecialchars($step['label']) . '</div>';
                echo '</div>';

                if ($si < $totalsteps - 1) {
                    if ($curlevel > $steplevel) {
                        $linecolor = '#43A047'; // Grün für erledigt
                    } elseif ($laststatuskey === 'ueberarbeitung_noetig' && $si === 1) {
                        // Orange für ueberarbeitung_noetig
                        $linecolor = '#FFA000';
                    } else {
                        // Grau für ausstehend
                        $linecolor = '#dee2e6';
                    }
                    echo '<div class="dhbwio-appbar-line" style="background:' . $linecolor . '"></div>';
                }
            }
        }

        echo '</div>'; // dhbwio-appbar-steps
        echo '</div>'; // dhbwio-appbar-container

        // Button zum erstellen des Learning Agreements
        // Wenn LA bereits erstellt ist dann Button zum neu Erstellen mit Abfrage

        if (!$laentries && !$canviewallla) {
            $createurl = new moodle_url('/mod/dhbwio/learning_agreement_formular.php', [
                'id' => $cm->id
            ]);
            echo html_writer::link($createurl, get_string('la_create_button', 'mod_dhbwio'), ['class' => 'btn btn-primary px-3 py-2']);
        } elseif (!$canviewallla) {
            $recreateurl = new moodle_url('/mod/dhbwio/learning_agreement_formular.php', [
                'id' => $cm->id,
                'action' => 'recreate',
                'la_entryid' => $laentry->id
            ]);
            echo html_writer::link(
                $recreateurl,
                get_string('la_recreate_button', 'mod_dhbwio'),
                ['class' => 'btn btn-warning px-3 py-2', 'onclick' => "return confirm('" . get_string('la_recreate_confirm', 'mod_dhbwio') . "');"]
            );
        }



        // Display Learning Agreement Table

        if (!empty($laentries)) {
            $templatecontext = la_manager::generate_la_table($laentries, $canviewallla, $cm);
            echo $OUTPUT->render_from_template('mod_dhbwio/learning_agreement_overview', $templatecontext);
        } else {
            echo html_writer::tag('p', get_string('no_learning_agreements', 'mod_dhbwio'));
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
				if ($frist->studiengang === 'alle') {
					$sgLabel = get_string('frist_alle_studiengaenge', 'mod_dhbwio');
				} elseif (is_numeric($frist->studiengang)) {
					$sgLabel = entry_manager::get_studyprogram_label((int) $frist->studiengang);
				} else {
					$sgLabel = format_string($frist->studiengang);
				}

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
					html_writer::link(
						$editurl,
						$OUTPUT->pix_icon('t/edit', get_string('edit')),
						['class' => 'btn btn-sm btn-outline-secondary']
					),
					html_writer::link(
						$deleteurl,
						$OUTPUT->pix_icon('t/delete', get_string('delete')),
						[
							'class'   => 'btn btn-sm btn-outline-danger',
							'onclick' => 'return confirm("' . get_string('frist_delete_confirm', 'mod_dhbwio') . '")',
						]
					),
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

	case 'files':
        $iscoordinator = has_capability('mod/dhbwio:manageuniversities', $context);

        echo '<div class="dhbwio-learningagreement">';
        echo '<h3>' . get_string('nav_learningagreement', 'mod_dhbwio') . '</h3>';

        $statuslabels = [
            'pending'  => get_string('la_status_pending', 'mod_dhbwio'),
            'approved' => get_string('la_status_approved', 'mod_dhbwio'),
            'rejected' => get_string('la_status_rejected', 'mod_dhbwio'),
        ];
        $doctypelabels = [
            'learning_agreement' => get_string('la_doctype_learning_agreement', 'mod_dhbwio'),
            'other_document'     => get_string('la_doctype_other_document', 'mod_dhbwio'),
        ];

        if (!$iscoordinator) {
            // Determine application status.
            try {
                $dataform_la = dataform_manager::get_course_dataform((int) $course->id);
                $dataid_la   = (int) $dataform_la->id;
            } catch (moodle_exception $e) {
                $dataid_la = 0;
            }
            $appstatus_la     = null;
            $appstatuskey_la  = null;
            if ($dataid_la > 0) {
                $userentries = entry_manager::get_user_entries($dataid_la, $USER->id);
                if (!empty($userentries)) {
                    $ufirst        = reset($userentries);
                    $appstatus_la  = status_manager::get_status((int) $ufirst->statusid);
                    $appstatuskey_la = $appstatus_la ? $appstatus_la->shortname : null;
                }
            }

            $isnachzureichen_tab = ($appstatuskey_la === 'nachzureichen');

            // ── Upload buttons (immer sichtbar) ────────────────────────────
            $lauploadurl = new moodle_url('/mod/dhbwio/learning_agreement.php', [
                'cmid'    => $cm->id,
                'doctype' => 'learning_agreement',
            ]);
            $otherurl = new moodle_url('/mod/dhbwio/learning_agreement.php', [
                'cmid'    => $cm->id,
                'doctype' => 'other_document',
            ]);
            echo '<div class="mb-3">';
            if ($isnachzureichen_tab) {
                echo html_writer::link($lauploadurl, get_string('la_la_btn', 'mod_dhbwio'), ['class' => 'btn btn-primary me-2']);
                echo html_writer::link($otherurl,    get_string('la_other_btn', 'mod_dhbwio'), ['class' => 'btn btn-secondary']);
            } else {
                echo '<button class="btn btn-secondary me-2" disabled>'
                    . get_string('la_create_btn', 'mod_dhbwio') . '</button>';
                echo html_writer::link($lauploadurl, get_string('la_la_btn', 'mod_dhbwio'), ['class' => 'btn btn-primary me-2']);
                echo html_writer::link($otherurl,    get_string('la_other_btn', 'mod_dhbwio'), ['class' => 'btn btn-outline-secondary']);
            }
            echo '</div>';

            // ── DELETE action ──────────────────────────────────────────────
            $deletelaid = optional_param('deletelaid', 0, PARAM_INT);
            if ($deletelaid > 0 && confirm_sesskey()) {
                $delrec = $DB->get_record('dhbwio_learning_agreements', ['id' => $deletelaid, 'dhbwio' => $dhbwio->id, 'userid' => $USER->id]);
                if ($delrec) {
                    $fs = get_file_storage();
                    $fs->delete_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $delrec->id);
                    $DB->delete_records('dhbwio_learning_agreements', ['id' => $delrec->id]);
                }
                redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']));
            }

            // ── Document history ────────────────────────────────────────────
            $allrecords = $DB->get_records('dhbwio_learning_agreements', [
                'dhbwio' => $dhbwio->id,
                'userid' => $USER->id,
            ], 'timecreated DESC');

            echo '<h5 class="mt-4">' . get_string('la_doc_history', 'mod_dhbwio') . '</h5>';

            if (empty($allrecords)) {
                echo '<p class="text-muted">' . get_string('la_no_upload_yet', 'mod_dhbwio') . '</p>';
            } else {
                $fs = get_file_storage();
                $histable = new html_table();
                $histable->head = [
                    get_string('la_col_doctype',  'mod_dhbwio'),
                    get_string('la_col_file',     'mod_dhbwio'),
                    get_string('la_col_status',   'mod_dhbwio'),
                    get_string('la_col_uploaded', 'mod_dhbwio'),
                    get_string('actions',         'mod_dhbwio'),
                ];
                $histable->attributes['class'] = 'table table-striped table-hover';

                foreach ($allrecords as $rec) {
                    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $rec->id, '', false);
                    if (!empty($files)) {
                        $file    = reset($files);
                        $fileurl = moodle_url::make_pluginfile_url(
                            $context->id, 'mod_dhbwio', 'learning_agreements', $rec->id,
                            $file->get_filepath(), $file->get_filename()
                        );
                        $filelink = '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a>';
                    } else {
                        $filelink = '-';
                    }

                    $statusbadge = '<span class="badge badge-'
                        . (['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$rec->status ?? 'pending'] ?? 'secondary')
                        . '">' . ($statuslabels[$rec->status ?? 'pending'] ?? $rec->status) . '</span>';

                    if (!empty($rec->comment)) {
                        $statusbadge .= '<br><small class="text-muted">' . s($rec->comment) . '</small>';
                    }

                    $deleteurl = new moodle_url('/mod/dhbwio/view.php', [
                        'id'         => $cm->id,
                        'tab'        => 'learningagreement',
                        'deletelaid' => $rec->id,
                        'sesskey'    => sesskey(),
                    ]);

                    $histable->data[] = [
                        $doctypelabels[$rec->doctype ?? 'learning_agreement'] ?? $rec->doctype,
                        $filelink,
                        $statusbadge,
                        userdate($rec->timecreated),
                        html_writer::link(
                            $deleteurl,
                            get_string('delete'),
                            [
                                'class'   => 'btn btn-sm btn-outline-danger',
                                'onclick' => 'return confirm("' . get_string('la_delete_confirm', 'mod_dhbwio') . '")',
                            ]
                        ),
                    ];
                }
                echo html_writer::table($histable);
            }
        } else {
            // Coordinator view: all uploaded documents grouped by student.
            $records = $DB->get_records('dhbwio_learning_agreements', ['dhbwio' => $dhbwio->id], 'userid ASC, timecreated DESC');

            if (empty($records)) {
                echo $OUTPUT->notification(get_string('la_no_submissions', 'mod_dhbwio'), 'info');
            } else {
                $fs = get_file_storage();

                $table = new html_table();
                $table->head = [
                    get_string('la_col_student',  'mod_dhbwio'),
                    get_string('la_col_doctype',  'mod_dhbwio'),
                    get_string('la_col_file',     'mod_dhbwio'),
                    get_string('la_col_submitted','mod_dhbwio'),
                    get_string('la_col_status',   'mod_dhbwio'),
                    get_string('actions',         'mod_dhbwio'),
                ];
                $table->attributes['class'] = 'table table-striped table-hover';

                foreach ($records as $rec) {
                    $student     = $DB->get_record('user', ['id' => $rec->userid]);
                    $studentname = $student ? fullname($student) : '(unbekannt)';

                    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $rec->id, '', false);
                    if (!empty($files)) {
                        $file    = reset($files);
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
                        . (['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$rec->status ?? 'pending'] ?? 'secondary')
                        . '">' . ($statuslabels[$rec->status ?? 'pending'] ?? $rec->status) . '</span>';

                    $table->data[] = [
                        $studentname,
                        $doctypelabels[$rec->doctype ?? 'learning_agreement'] ?? $rec->doctype,
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

	default:
		// Default to universities view
		$renderer = $PAGE->get_renderer('mod_dhbwio');
		$renderer->display_universities_list($dhbwio, $cm);
		break;


			$lauploadurl = new moodle_url('/mod/dhbwio/learning_agreement.php', [
				'cmid'    => $cm->id,
				'doctype' => 'other_document',
			]);

			// ── DELETE action ──────────────────────────────────────────────
			$deletelaid = optional_param('deletelaid', 0, PARAM_INT);
			if ($deletelaid > 0 && confirm_sesskey()) {
				$delrec = $DB->get_record('dhbwio_learning_agreements', ['id' => $deletelaid, 'dhbwio' => $dhbwio->id, 'userid' => $USER->id]);
				if ($delrec) {
					$fs = get_file_storage();
					$fs->delete_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $delrec->id);
					$DB->delete_records('dhbwio_learning_agreements', ['id' => $delrec->id]);
				}
				redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']));
			}

			// ── Document history ────────────────────────────────────────────
			$allrecords = $DB->get_records('dhbwio_learning_agreements', [
				'dhbwio' => $dhbwio->id,
				'userid' => $USER->id,
			], 'timecreated DESC');

			echo '<h5 class="mt-2">' . get_string('la_doc_history', 'mod_dhbwio') . '</h5>';

			if (empty($allrecords)) {
				echo '<p class="text-muted">' . get_string('la_no_upload_yet', 'mod_dhbwio') . '</p>';
			} else {
				$fs = get_file_storage();
				$histable = new html_table();
				$histable->head = [
					get_string('la_col_doctype',  'mod_dhbwio'),
					get_string('la_col_file',     'mod_dhbwio'),
					get_string('la_col_status',   'mod_dhbwio'),
					get_string('la_col_uploaded', 'mod_dhbwio'),
					get_string('actions',         'mod_dhbwio'),
				];
				$histable->attributes['class'] = 'table table-striped table-hover';

				foreach ($allrecords as $rec) {
					$files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $rec->id, '', false);
					if (!empty($files)) {
						$file    = reset($files);
						$fileurl = moodle_url::make_pluginfile_url(
							$context->id, 'mod_dhbwio', 'learning_agreements', $rec->id,
							$file->get_filepath(), $file->get_filename()
						);
						$filelink = '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a>';
					} else {
						$filelink = '-';
					}

					$statusbadge = '<span class="badge badge-'
						. (['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$rec->status ?? 'pending'] ?? 'secondary')
						. '">' . ($statuslabels[$rec->status ?? 'pending'] ?? $rec->status) . '</span>';

					if (!empty($rec->comment)) {
						$statusbadge .= '<br><small class="text-muted">' . s($rec->comment) . '</small>';
					}

					$deleteurl = new moodle_url('/mod/dhbwio/view.php', [
						'id'         => $cm->id,
						'tab'        => 'learningagreement',
						'deletelaid' => $rec->id,
						'sesskey'    => sesskey(),
					]);

					$histable->data[] = [
						$doctypelabels[$rec->doctype ?? 'learning_agreement'] ?? $rec->doctype,
						$filelink,
						$statusbadge,
						userdate($rec->timecreated),
						html_writer::link(
							$deleteurl,
							get_string('delete'),
							[
								'class'   => 'btn btn-sm btn-outline-danger',
								'onclick' => 'return confirm("' . get_string('la_delete_confirm', 'mod_dhbwio') . '")',
							]
						),
					];
				}
				echo html_writer::table($histable);
			}

			// ── Moodle File Upload button ────────────────────────────────────
			echo '<div class="mt-3">';
			echo html_writer::link($lauploadurl, get_string('la_moodle_upload_btn', 'mod_dhbwio'), ['class' => 'btn btn-primary']);
			echo '</div>';
		} else {
			// Coordinator view: inline comment form per entry.

			// Handle inline comment POST.
			$inlineaction = optional_param('la_inline_action', '', PARAM_ALPHA);
			if ($inlineaction === 'savecomment' && confirm_sesskey()) {
				$inlinelaid    = required_param('laid', PARAM_INT);
				$inlinestatus  = required_param('la_status', PARAM_ALPHA);
				$inlinecomment = optional_param('la_comment', '', PARAM_TEXT);
				$inlinerec     = $DB->get_record('dhbwio_learning_agreements', ['id' => $inlinelaid, 'dhbwio' => $dhbwio->id]);
				if ($inlinerec && in_array($inlinestatus, ['pending', 'approved', 'rejected'])) {
					$DB->update_record('dhbwio_learning_agreements', (object)[
						'id'      => $inlinelaid,
						'status'  => $inlinestatus,
						'comment' => $inlinecomment,
					]);
				}
				redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']));
			}

			$records = $DB->get_records('dhbwio_learning_agreements', ['dhbwio' => $dhbwio->id], 'userid ASC, timecreated DESC');

			if (empty($records)) {
				echo $OUTPUT->notification(get_string('la_no_submissions', 'mod_dhbwio'), 'info');
			} else {
				$fs = get_file_storage();
				$statusbadgecolors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

				echo '<table class="table table-striped table-hover">';
				echo '<thead><tr>';
				echo '<th>' . get_string('la_col_student',  'mod_dhbwio') . '</th>';
				echo '<th>' . get_string('la_col_doctype',  'mod_dhbwio') . '</th>';
				echo '<th>' . get_string('la_col_file',     'mod_dhbwio') . '</th>';
				echo '<th>' . get_string('la_col_submitted','mod_dhbwio') . '</th>';
				echo '<th>' . get_string('la_col_status',   'mod_dhbwio') . '</th>';
				echo '<th>' . get_string('la_col_comment',  'mod_dhbwio') . '</th>';
				echo '</tr></thead><tbody>';

				foreach ($records as $rec) {
					$student     = $DB->get_record('user', ['id' => $rec->userid]);
					$studentname = $student ? fullname($student) : '(unbekannt)';

					$files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $rec->id, '', false);
					if (!empty($files)) {
						$file    = reset($files);
						$fileurl = moodle_url::make_pluginfile_url(
							$context->id, 'mod_dhbwio', 'learning_agreements', $rec->id,
							$file->get_filepath(), $file->get_filename()
						);
						$filelink = '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a>';
					} else {
						$filelink = '-';
					}

					$recstatus = $rec->status ?? 'pending';
					$statusbadge = '<span class="badge badge-' . ($statusbadgecolors[$recstatus] ?? 'secondary') . '">'
						. ($statuslabels[$recstatus] ?? $recstatus) . '</span>';

					$formid  = 'la-comment-form-' . $rec->id;
					$formurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'learningagreement']);

					$statusoptions = '';
					foreach (['pending' => $statuslabels['pending'], 'approved' => $statuslabels['approved'], 'rejected' => $statuslabels['rejected']] as $val => $label) {
						$sel = ($recstatus === $val) ? ' selected' : '';
						$statusoptions .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
					}

					$inlineform = '
						<form id="' . $formid . '" method="post" action="' . $formurl . '">
							<input type="hidden" name="sesskey" value="' . sesskey() . '">
							<input type="hidden" name="la_inline_action" value="savecomment">
							<input type="hidden" name="laid" value="' . $rec->id . '">
							<div class="d-flex gap-2 align-items-start flex-wrap">
								<select name="la_status" class="form-select form-select-sm" style="width:auto">' . $statusoptions . '</select>
								<textarea name="la_comment" class="form-control form-control-sm" rows="2" style="min-width:180px" placeholder="' . get_string('la_comment_label', 'mod_dhbwio') . '">' . s($rec->comment ?? '') . '</textarea>
								<button type="submit" class="btn btn-sm btn-primary">' . get_string('la_save_review', 'mod_dhbwio') . '</button>
							</div>
						</form>';

					echo '<tr>';
					echo '<td>' . s($studentname) . '</td>';
					echo '<td>' . ($doctypelabels[$rec->doctype ?? 'learning_agreement'] ?? $rec->doctype) . '</td>';
					echo '<td>' . $filelink . '</td>';
					echo '<td>' . userdate($rec->timecreated) . '</td>';
					echo '<td>' . $statusbadge . '</td>';
					echo '<td>' . $inlineform . '</td>';
					echo '</tr>';
				}

				echo '</tbody></table>';
			}
		}

		echo '</div>';
		break;
}

echo $OUTPUT->footer();
