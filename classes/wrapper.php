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

    /** The files that should be included in the wrapper. */
    const FILES = [
        // Path in zip.               // Relative path.

        // Files missing in this list are:
        //  1. index.html: need to change data-source.
        //  2. imsmanifest.xml: need to change some metadata.
    ];

    /**
     * Create a .zip archive which is what should be distributed towards clients.
     *
     * @param object $scormremote instance
     * @param int $clientid
     * @return \stored_file
     */
    public static function create(&$scormremote, $clientid) {
        global $CFG;

        // Get the cm for context.
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        $context = \context_module::instance($scormremote->coursemodule);

        // Create the archive.
        $zipwriter = archive_writer::get_file_writer(time().'-wrapped.zip', archive_writer::ZIP_WRITER);
        $zipwriter->add_file_from_string('index.html', self::get_client_index($context->id, $clientid));
        $zipwriter->add_file_from_string('imsmanifest.xml', self::get_client_imsmanifest());
        foreach (self::FILES as $pathtofileinzip => $filetoadd) {
            $zipwriter->add_file_from_filepath($pathtofileinzip, $filetoadd);
        }
        $zipwriter->finish();

        // Create a filename.
        $client = new client($clientid);
        $filenamehead = preg_replace("/[^A-Za-z0-9 ]/", '', $scormremote->name);
        $filenametail = preg_replace("/[^A-Za-z0-9 ]/", '', $client->get('name'));
        $filename = "{$filenamehead} - {$filenametail}.zip"; // Remove all non-alphanumeric and add .zip.

        $fileinfo = [
            'component' => 'mod_scormremote',
            'filearea'  => self::FILEAREA,
            'itemid'    => $clientid,
            'contextid' => $context->id,
            'filepath'  => '/',
            'filename'  => $filename,
        ];

        // Delete the old store the new archive in the filesystem.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_scormremote', self::FILEAREA, $clientid);
        return $fs->create_file_from_pathname($fileinfo, $zipwriter->get_path_to_zip());
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
     * Returns client specific string value for index.html.
     *
     * @param int $contextid
     * @param int $clientid
     * @return string
     */
    public static function get_client_index($contextid, $clientid) {
        global $CFG;

        $url = "{$CFG->wwwroot}/pluginfile.php/{$contextid}/mod_scormremote/remote/{$clientid}/index.html";
        $index = new \DOMDocument();
        $index->loadHTMLFile(__DIR__. '/../scol-r/index.html');

        $body = $index->getElementsByTagName('body')[0];
        $body->setAttribute('data-source', $url);

        return  $index->saveHTML();
    }

    /**
     * Returns client specific string value for imsmanifest.xml
     *
     * @return string
     */
    public static function get_client_imsmanifest() {
        $manifest = new \DOMDocument();
        $manifest->load(__DIR__. '/../scol-r/imsmanifest.xml');
        return  $manifest->saveXML();
    }
}