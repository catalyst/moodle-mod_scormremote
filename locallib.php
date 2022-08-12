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
 * Library of internal classes and functions for module SCORM remote
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
 * Extracts scrom package, sets up all variables.
 * Called whenever scorm changes
 * @param object $scorm instance - fields are updated and changes saved into database
 * @param bool $full force full update if true
 * @return void
 */
function scormremote_parse($scormremote, $full) {
    global $DB;

    if (!isset($scormremote->cmid)) {
        $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
        $scormremote->cmid = $cm->id;
    }
    $context = context_module::instance($scormremote->cmid);
    $newhash = $scormremote->sha1hash;

    $fs = get_file_storage();
    $packagefile = false;
    $packagefileimsmanifest = false;

    if ($packagefile = $fs->get_file($context->id, 'mod_scormremote', 'package', 0, '/', $scormremote->reference)) {
        if ($packagefile->is_external_file()) { // Get zip file so we can check it is correct.
            $packagefile->import_external_file_contents();
        }
        $newhash = $packagefile->get_contenthash();
        if (strtolower($packagefile->get_filename()) == 'imsmanifest.xml') {
            $packagefileimsmanifest = true;
        }
    } else {
        $newhash = null;
    }


    if ($packagefile) {
        if (!$full and $packagefile and $scormremote->sha1hash === $newhash) {
            if ($packagefileimsmanifest || $fs->get_file($context->id, 'mod_scorm', 'content', 0, '/', 'imsmanifest.xml')) {
                // No need to update.
                return;
            }
        }
        if (!$packagefileimsmanifest) {
            // Now extract files.
            $fs->delete_area_files($context->id, 'mod_scormremote', 'content');

            $packer = get_file_packer('application/zip');
            $packagefile->extract_to_storage($packer, $context->id, 'mod_scormremote', 'content', 0, '/');
        }

    } else if (!$full) {
        return;
    }

    $scormremote->sha1hash = $newhash;
    $DB->update_record('scormremote', $scormremote);
}