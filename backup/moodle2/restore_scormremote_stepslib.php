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
 * Defines restore_scormremote_activity_structure_step class
 *
 * @package     mod_scormremote
 * @subpackage  backup-moodle2
 * @author      Glenn Poder <glennpoder@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_scormremote_activity_task
 */

/**
 * Structure step to restore one scormremote activity
 */
class restore_scormremote_activity_structure_step extends restore_activity_structure_step {

    /**
     * Structure step to restore one scorm remote activity
     */
    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('scormremote', '/activity/scormremote');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process scorm remote information
     * @param array $data
     * @return void
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_scormremote($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the scormremote record.
        $newitemid = $DB->insert_record('scormremote', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Update package file.
     */
    protected function after_execute() {
        global $DB;
        $this->add_related_files('mod_scormremote', 'intro', null);
        $this->add_related_files('mod_scormremote', 'content', null);
        $this->add_related_files('mod_scormremote', 'package', null);
    }
}
