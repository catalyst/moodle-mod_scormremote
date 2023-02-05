<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Scormremote module data generator class.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scormremote_generator extends testing_module_generator {

    /**
     * Create scormremote instance.
     *
     * @param array|stdClass $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/resourcelib.php');
        return parent::create_instance($record, (array)$options);
    }
}
