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

use core_files\archive_writer;

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
    public static function store(&$scormremote) {
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
    public static function parse(&$scormremote) {
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
     * Download a .zip archive which is what should be distributed towards clients.
     *
     * @param object $scormremote instance
     * @param string $filename
     * @return \stored_file
     */
    public static function download_wrapper($scormremote, $filename) {
        global $CFG, $OUTPUT;

        // Local variables.
        $context = \mod_scormremote\utils::get_context($scormremote);
        $manifest = simplexml_load_string(\mod_scormremote\utils::get_scormremote_imsmanifest($scormremote));
        $zip = archive_writer::get_stream_writer($filename, archive_writer::ZIP_WRITER);

        // From this instance's manifest, we replacing all files by index files. Each resource (SCO) will have it's own index file
        // names sco_1.html, sco_2.html, sco_3.html. This html is generated from the secondlayer.mustache and contains the
        // datasource which points towards the third layer, but contains the filepath for the original file.
        $count = 0; // The $key => value, doesn't appear to work. So maintain counter ourself.
        foreach ($manifest->resources->resource as $resource) {
            // Remove all the files from each resource.
            while (count($resource->file) > 0) {
                unset($resource->file[0]);
            }

            // All resources must point towards their own layer 2 containing a link to the data source.
            $datasource = \moodle_url::make_pluginfile_url(
                $context->id,
                'mod_scormremote',
                'remote',                     // THIS is pointing towards the third layer.
                0,
                '/',
                $resource->attributes()->href // The original file path.
            );

            $templatedata = [
                'datasource'       => $datasource,
                'jssource'         => $CFG->wwwroot . '/mod/scormremote/amd/src/layer2.js',
            ];
            $resourcefile = $OUTPUT->render_from_template('mod_scormremote/secondlayer', $templatedata);
            $resourcefilename = "sco_$count.html";

            // Add the created file from template to the archive.
            $zip->add_file_from_string($resourcefilename, $resourcefile);

            $file = $resource->addChild('file');
            $file->addAttribute('href', $resourcefilename);
            $resource->attributes()->href = $resourcefilename;
            $count++;
        }

        // Add the manifest and finish.
        $zip->add_file_from_string('imsmanifest.xml', $manifest->asXML());
        $zip->finish();
        exit();
    }
}
