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

namespace mod_dataform\grades;

defined('MOODLE_INTERNAL') || die();

class gradeitems extends \core_grades\component_gradeitems
    implements \core_grades\local\gradeitem\advancedgrading_mapping {

    public static function get_itemname_mapping_for_component(string $component): array {
        return [
            0 => '',
        ];
    }

    public static function get_advancedgrading_itemnames(): array {
        return [];
    }
}
