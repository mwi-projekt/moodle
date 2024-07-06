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
 * View management setting.
 *
 * @package    mod_dataform
 * @copyright  2014 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dataform\setting;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/adminlib.php");

class managedataformview extends \admin_setting {
    /**
     * Calls parent::__construct with specific arguments
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('mod_dataform_manageui', get_string('manageviews', 'mod_dataform'), '', '');
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Always returns '', does not write anything.
     *
     * @param mixed $data ignored
     * @return string Always returns ''
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Checks if $query is one of the available dataformview plugins.
     *
     * @param string $query The string to search for
     * @return bool Returns true if found, false if not
     */
    public function is_related($query) {
        if (parent::is_related($query)) {
            return true;
        }

        $query = \core_text::strtolower($query);
        $plugins = \core_component::get_plugin_list('dataformview');
        foreach ($plugins as $plugin => $fulldir) {
            if (strpos(\core_text::strtolower($plugin), $query) !== false) {
                return true;
            }
            $localised = get_string('pluginname', "dataformview_$plugin");
            if (strpos(\core_text::strtolower($localised), $query) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Builds the XHTML to display the control.
     *
     * @param string $data Unused
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT, $PAGE;

        $plugincat = 'dataformview';

        // Display strings.
        $strup = get_string('up');
        $strdown = get_string('down');
        $strsettings = get_string('settings');
        $strenable = get_string('enable');
        $strdisable = get_string('disable');
        $struninstall = get_string('uninstallplugin', 'core_admin');
        $strversion = get_string('version');
        $strinstances = get_string('instances', 'mod_dataform');

        $pluginmanager = \core_plugin_manager::instance();
        $available = \core_component::get_plugin_list($plugincat);
        $enabled = get_config('mod_dataform', "enabled_$plugincat");
        if (!$enabled) {
            $enabled = array();
        } else {
            $enabled = array_flip(explode(',', $enabled));
        }

        $allplugins = array();
        foreach ($enabled as $key => $plugin) {
            $allplugins[$key] = true;
            $enabled[$key] = true;
        }
        foreach ($available as $key => $plugin) {
            $allplugins[$key] = true;
            $available[$key] = true;
        }

        $return = $OUTPUT->heading(get_string('availableplugins', 'mod_dataform'), 3, 'main', true);
        $return .= $OUTPUT->box_start('generalbox');

        $table = new \html_table();
        $table->head = array(
            get_string('name'),
            $strversion,
            $strinstances,
            $strenable,
            $strup . '/' . $strdown,
            $strsettings,
            $struninstall
        );
        $table->colclasses = array(
            'leftalign',
            'centeralign',
            'centeralign',
            'centeralign',
            'centeralign',
            'centeralign',
            'centeralign'
        );
        $table->id = $plugincat. 'plugins';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();

        // Iterate through the plugins and add to the display table.
        $updowncount = 1;
        $plugincount = count($enabled);
        $url = new \moodle_url('/mod/dataform/admin/plugins.php', array('sesskey' => sesskey()));
        $printed = array();
        foreach ($allplugins as $plugin => $unused) {
            $plugintype = $plugincat. '_'. $plugin;
            $plugininfo = $pluginmanager->get_plugin_info($plugintype);
            // Name.
            if (get_string_manager()->string_exists('pluginname', $plugintype)) {
                $name = get_string('pluginname', $plugintype);
            } else {
                $name = $plugintype;
            }

            // Version.
            $version = get_config($plugintype, 'version');
            if ($version === false) {
                $version = '';
            }

            // Instance count.
            $instancecount = $plugininfo->get_instance_count($plugin);

            // Hide/show links.
            if (isset($enabled[$plugin])) {
                $aurl = new \moodle_url($url, array('action' => 'disable', 'plugin' => $plugintype));
                $hideshow = "<a href=\"$aurl\">";
                $hideshow .= "<img src=\"" . $OUTPUT->pix_url('t/hide') . "\" class=\"iconsmall\" alt=\"$strdisable\" /></a>";
                $isenabled = true;
                $displayname = "<span>$name</span>";
            } else {
                if (isset($available[$plugin])) {
                    $aurl = new \moodle_url($url, array('action' => 'enable', 'plugin' => $plugintype));
                    $hideshow = "<a href=\"$aurl\">";
                    $hideshow .= "<img src=\"" . $OUTPUT->pix_url('t/show') . "\" class=\"iconsmall\" alt=\"$strenable\" /></a>";
                    $isenabled = false;
                    $displayname = "<span class=\"dimmed_text\">$name</span>";
                } else {
                    $hideshow = '';
                    $isenabled = false;
                    $displayname = '<span class="notifyproblem">' . $name . '</span>';
                }
            }
            if ($PAGE->theme->resolve_image_location('icon', $plugintype, false)) {
                $icon = $OUTPUT->pix_icon('icon', '', $plugintype, array('class' => 'icon pluginicon'));
            } else {
                $icon = $OUTPUT->pix_icon('spacer', '', 'moodle', array('class' => 'icon pluginicon noicon'));
            }

            // Up/down link (only if plugin is enabled).
            $updown = '';
            if ($isenabled) {
                if ($updowncount > 1) {
                    $aurl = new \moodle_url($url, array('action' => 'up', 'plugin' => $plugintype));
                    $updown .= "<a href=\"$aurl\">";
                    $updown .= "<img src=\"" . $OUTPUT->pix_url('t/up') . "\" alt=\"$strup\" class=\"iconsmall\" /></a>&nbsp;";
                } else {
                    $updown .= "<img src=\"" . $OUTPUT->pix_url('spacer') . "\" class=\"iconsmall\" alt=\"\" />&nbsp;";
                }
                if ($updowncount < $plugincount) {
                    $aurl = new \moodle_url($url, array('action' => 'down', 'plugin' => $plugintype));
                    $updown .= "<a href=\"$aurl\">";
                    $updown .= "<img src=\"" . $OUTPUT->pix_url('t/down') . "\" alt=\"$strdown\" class=\"iconsmall\" /></a>";
                } else {
                    $updown .= "<img src=\"" . $OUTPUT->pix_url('spacer') . "\" class=\"iconsmall\" alt=\"\" />";
                }
                ++$updowncount;
            }

            // Add settings link.
            if (!$version) {
                $settings = '';
            } else {
                if ($surl = $plugininfo->get_settings_url()) {
                    $settings = \html_writer::link($surl, $strsettings);
                } else {
                    $settings = '';
                }
            }

            // Add uninstall info.
            $uninstall = '';
            if ($uninstallurl = \core_plugin_manager::instance()->get_uninstall_url($plugintype, 'manage')) {
                $uninstall = \html_writer::link($uninstallurl, $struninstall);
            }

            // Add a row to the table.
            $table->data[] = array(
                $icon . $displayname,
                $version,
                $instancecount,
                $hideshow,
                $updown,
                $settings,
                $uninstall
            );

            $printed[$plugin] = true;
        }

        $return .= \html_writer::table($table);
        $return .= get_string('configplugins', 'mod_dataform') . '<br />' . get_string('tablenosave', 'admin');
        $return .= $OUTPUT->box_end();
        return highlight($query, $return);
    }
}
