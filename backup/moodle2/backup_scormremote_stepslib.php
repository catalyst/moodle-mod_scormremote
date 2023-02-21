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
 * Defines backup_scormremote_activity_structure_step class
 *
 * @package     mod_scormremote
 * @subpackage  backup-moodle2
 * @author      Glenn Poder <glennpoder@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_scormremote_activity_task
 */

/**
 * Define the complete scormremote structure for backup, with file and id annotations
 */
class backup_scormremote_activity_structure_step extends backup_activity_structure_step {

    /**
     * Structure step to backup one scorm remote activity
     */
    protected function define_structure() {

        // Define each element separated.

        $scormremote = new backup_nested_element('scormremote', array('id'), array(
            'name', 'reference', 'sha1hash', 'intro', 'introformat', 'timecreated', 'timemodified'));

        // Define sources.
        $scormremote->set_source_table('scormremote', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations.
        $scormremote->annotate_files('mod_scormremote', 'intro', null); // This file area hasn't itemid
        $scormremote->annotate_files('mod_scormremote', 'content', null); // This file area hasn't itemid
        $scormremote->annotate_files('mod_scormremote', 'package', null); // This file area hasn't itemid.

        // Return the root element (scormremote), wrapped into standard activity structure.
        return $this->prepare_activity_structure($scormremote);
    }
}
