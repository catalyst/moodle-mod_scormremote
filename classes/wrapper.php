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

defined('MOODLE_INTERNAL') || die();

/**
 * Class contains static methods for handling SCORM wrapper.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wrapper {
        /** The filearea in which wrappers are stored. */
    const FILEAREA = 'wrappers';

    /**
     * Create a .zip archive which is what should be distributed towards clients.
     *
     * @param object $scormremote instance
     * @param int $clientid
     * @return \stored_file
     */
    public static function create(&$scormremote, $clientid) {
        global $CFG, $OUTPUT;

        // Local variables.
        $context = \mod_scormremote\utils::get_context($scormremote);
        $manifest = simplexml_load_string(\mod_scormremote\utils::get_scormremote_imsmanifest($scormremote));
        $zip = archive_writer::get_file_writer(time().'-wrapped.zip', archive_writer::ZIP_WRITER);
        $client = new client($clientid);
        $fs = get_file_storage();
        $fileinfo = [
            'component' => 'mod_scormremote',
            'filearea'  => self::FILEAREA,
            'itemid'    => $clientid,
            'contextid' => $context->id,
            'filepath'  => '/',
        ];

        // From this instance's manifest, we replacing all files by index files. Each resource (SCO) will have it's own index file
        // names sco_1.html, sco_2.html, sco_3.html. This html is generated from the secondlayer.mustache and contains the
        // datasource which points towards the third layer, but contains the filepath for the original file.
        $count = 0; // $key => value, doesn't appear to work.
        foreach ($manifest->resources->resource as $resource) {
            // Remove all the files from each resource.
            while(count($resource->file) > 0) {
                unset($resource->file[0]);
            }

            // All resources must point towards their own layer 2 containing a link to the data source.
            $datasource = \moodle_url::make_pluginfile_url(
                $fileinfo['contextid'],
                $fileinfo['component'],
                'remote',                     // THIS is pointing towards the third layer.
                $fileinfo['itemid'],
                $fileinfo['filepath'],
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

        $filenamehead = preg_replace("/[^A-Za-z0-9 ]/", '', $scormremote->name);
        $filenametail = preg_replace("/[^A-Za-z0-9 ]/", '', $client->get('name'));
        $fileinfo['filename'] = "{$filenamehead} - {$filenametail}.zip"; // Remove all non-alphanumeric and add .zip.

        // Delete the old store the new archive in the filesystem.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_scormremote', self::FILEAREA, $clientid);
        return $fs->create_file_from_pathname($fileinfo, $zip->get_path_to_zip());
    }

    /**
     * Delete a wrapper from the database and filessystem.
     *
     * @param object $scormremote instance
     * @param int $clientid
     * @return bool success
     */
    public static function delete(&$scormremote, $clientid) {
        // Get the cm for context.
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        $context = \context_module::instance($scormremote->coursemodule);

        $fs = get_file_storage();
        return $fs->delete_area_files($context->id, 'mod_scormremote', self::FILEAREA, $clientid);
    }

    /**
     * Download a .zip archive which is what should be distributed towards clients.
     *
     * @param object $scormremote instance
     * @param int $clientid
     * @return \stored_file
     */
    public static function download($scormremote, $filename) {
        global $CFG, $OUTPUT;

        // Local variables.
        $context = \mod_scormremote\utils::get_context($scormremote);
        $manifest = simplexml_load_string(\mod_scormremote\utils::get_scormremote_imsmanifest($scormremote));
        $zip = archive_writer::get_stream_writer($filename, archive_writer::ZIP_WRITER);

        // From this instance's manifest, we replacing all files by index files. Each resource (SCO) will have it's own index file
        // names sco_1.html, sco_2.html, sco_3.html. This html is generated from the secondlayer.mustache and contains the
        // datasource which points towards the third layer, but contains the filepath for the original file.
        $count = 0; // $key => value, doesn't appear to work.
        foreach ($manifest->resources->resource as $resource) {
            // Remove all the files from each resource.
            while(count($resource->file) > 0) {
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