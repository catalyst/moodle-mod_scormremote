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
 * Defines backup_scormremote_activity_task class
 *
 * @package     mod_scormremote
 * @subpackage  backup-moodle2
 * @author      Glenn Poder <glennpoder@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/scormremote/backup/moodle2/backup_scormremote_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the SCORMREMOTE instance
 */
class backup_scormremote_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the scormremote.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_scormremote_activity_structure_step('scormremote_structure', 'scormremote.xml'));
    }

    /**
     * Code the transformations to perform in the activity in order to get transportable (encoded) links
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return array|string|string[]|null
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of scormremotes.
        $search = "/(".$base."\/mod\/scormremote\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SCORMREMOTEINDEX*$2@$', $content);

        // Link to scormremote view by moduleid.
        $search = "/(".$base."\/mod\/scormremote\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SCORMREMOTEVIEWBYID*$2@$', $content);

        return $content;
    }
}
