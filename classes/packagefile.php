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

namespace mod_scormremote;

defined('MOODLE_INTERNAL') || die();

/**
 * Class contains static methods for handling SCORM packagefile.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class packagefile {
    /**
     * This method stores the uploaded .zip or .xml SCORM package retrieved from the mod form.
     *
     * @param object $scormremote instance - ields are updated and changes saved into database
     * @return void
     */
    public static function scormremote_store(&$scormremote) {
        global $DB;
        if (!isset($scormremote->packagefile) || empty($scormremote->packagefile)) {
            // Nothing to do.
            return;
        }

        if (!isset($scormremote->id)) {
            // Must have this for updating.
            $scormremote->id = $scormremote->instance;
        }

        $fs = get_file_storage();
        $context = \context_module::instance($scormremote->coursemodule);
        $component = 'mod_scormremote';
        $filearea = 'package';


        // Remove any old SCORM packages for this cm instance.
        $fs->delete_area_files($context->id, $component, $filearea);

        // Save from draft to real file.
        $options = [
            'subdirs' => 0,
            'maxfiles' => 1
        ];
        file_save_draft_area_files($scormremote->packagefile, $context->id, $component, $filearea, 0, $options);

        // Get the just saved files.
        $allfiles = $fs->get_area_files($context->id, $component, $filearea, 0, '', false); // Should be one file.
        $file = reset($allfiles);

        // Save the filename and hash to the cm instance.
        $scormremote->reference = $file->get_filename();

        $DB->update_record('scormremote', $scormremote);
    }

    /**
     * Extracts scrom package, sets up all variables.
     * Called whenever scorm package changes.
     *
     * @param object $scormremote instance - fields are updated and changes saved into database
     * @return void
     */
    public static function scormremote_parse(&$scormremote) {
        global $DB;

        $fs = get_file_storage();
        $context = \context_module::instance($scormremote->coursemodule);
        $component = 'mod_scormremote';
        $filearea = 'content';
        $newhash = null;
        $packagefileimsmanifest = false;

        $packagefile = $fs->get_file($context->id, $component, 'package', 0, '/', $scormremote->reference);

        if ($packagefile === false) {
            // Can't do anything here.
            return;
        }

        // Get zip file so we can check it is correct.
        if ($packagefile->is_external_file()) {
            $packagefile->import_external_file_contents();
        }
        if (strtolower($packagefile->get_filename()) == 'imsmanifest.xml') {
            $packagefileimsmanifest = true;
        }
        $newhash = $packagefile->get_contenthash();

        if ($newhash == $scormremote->sha1hash) {
            // Not gonna do the same thing again.
            return;
        }

        // Delete old files.
        $fs->delete_area_files($context->id, $component, $filearea);

        if (!$packagefileimsmanifest) {
            // Extract zip.
            $packer = get_file_packer('application/zip');
            $packagefile->extract_to_storage($packer, $context->id, $component, $filearea, 0, '/');
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
    public static function add_scormagain_html($html) {
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
}