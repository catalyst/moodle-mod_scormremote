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
 *
 * @param object $scormremote instance - fields are updated and changes saved into database
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
    if (!isset($scormremote->sha1hash)) {
        $scormremote->sha1hash = null;
    }
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
    }

    if ($packagefile) {
        if (!$packagefileimsmanifest) {
            // Delete old files.
            $fs->delete_area_files($context->id, 'mod_scormremote', 'content');

            // Now extract files.
            $packer = get_file_packer('application/zip');
            $packagefile->extract_to_storage($packer, $context->id, 'mod_scormremote', 'content', 0, '/');

            $fileinfo = [
                'component' => 'mod_scormremote',
                'filearea'  => 'content',
                'itemid'    => 0,
                'contextid' => $context->id,
                'filepath'  => '/',
            ];

            // Add javascript to all .html at the root level.
            $filerecords = $DB->get_records('files', $fileinfo, 'filename');
            foreach($filerecords as $filerecord) {
                if (pathinfo($filerecord->filename, PATHINFO_EXTENSION) != 'html') {
                    continue;
                }

                $fileinfo['filename'] = $filerecord->filename;

                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

                $newfilecontents = add_scormagain_html($file->get_content());
                $file->delete();
                $fs->create_file_from_string($fileinfo, $newfilecontents);
            }
        }
    }

    $scormremote->sha1hash = $newhash;
    $DB->update_record('scormremote', $scormremote);
}


/**
 * Adds required javascript to a HTML string.
 *
 * @param string $html
 * @return mixed Returns the content as a string or returns false when it can't append properly.
 */
function add_scormagain_html($html) {
    global $CFG;

    // Find the closing body tag.
    $pos = strpos($html, '</body>');

    // No closing body tag.
    if ($pos === false) {
        return false;
    }

    // Is there more then one body tag?
    if (strpos($html, '</body>', $pos + 1) !== false) {
        return false;
    }

    // Create external scormagain javascript element.
    $external = \html_writer::script('', $CFG->wwwroot.'/mod/scormremote/scorm-again/dist/scorm12.min.js');

    // Create javascript tag containing the API calls.
    $script = \html_writer::script("var settings = {};window.API = new Scorm12API(settings);window.parent = window;");

    return substr_replace($html, $external.$script, $pos, 0);
}