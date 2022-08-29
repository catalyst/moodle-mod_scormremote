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
 * Library of interface functions and constants.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function scormremote_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_scormremote into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $scormremote An object from the form.
 * @param mod_scormremote_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function scormremote_add_instance($scormremote, $mform = null) {
    global $CFG, $DB;

    $scormremote->timecreated = time();
    $scormremote->id = $DB->insert_record('scormremote', $scormremote);

    // Update course module record - from now on this instance properly exists and all function may be used.
    $DB->set_field('course_modules', 'instance', $scormremote->id, array('id' => $scormremote->coursemodule));

    // Store the package and verify.
    if (!empty($scormremote->packagefile)) {
        // It's a new instance so sha1 must be empty.
        $scormremote->sha1hash = null;

        // Store drafted file.
        \mod_scormremote\packagefile::scormremote_store($scormremote);

        // Parse the uploaded package.
        \mod_scormremote\packagefile::scormremote_parse($scormremote);
    }

    return $scormremote->id;
}

/**
 * Updates an instance of the mod_scormremote in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $scormremote An object from the form in mod_form.php.
 * @param mod_scormremote_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function scormremote_update_instance($scormremote, $mform = null) {
    global $DB, $CFG;

    // Store the package and verify.
    if (!empty($scormremote->packagefile)) {
        // This might be the same file.
        $old = $DB->get_record('scormremote', ['id' => $scormremote->instance]);
        $scormremote->sha1hash = $old->sha1hash;
        unset($old);

        // Store drafted file.
        \mod_scormremote\packagefile::scormremote_store($scormremote);

        // Parse the uploaded package.
        \mod_scormremote\packagefile::scormremote_parse($scormremote);
    }

    return true;
}

/**
 * Removes an instance of the mod_scormremote from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function scormremote_delete_instance($id) {
    global $DB;

    $cm = get_coursemodule_from_instance('scormremote', $id);
    $context = \context_module::instance($cm->id);

    // Delete all the files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_scormremote', 'package');
    $fs->delete_area_files($context->id, 'mod_scormremote', 'content');
    unset($fs);

    $exists = $DB->get_record('scormremote', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('scormremote', array('id' => $id));

    return true;
}


/**
 * Serves scorm content, introduction images and packages. Implements needed access control ;-)
 *
 * @package  mod_scormremote
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function scormremote_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $OUTPUT;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // // Authenticate.
    // if (!isset($_GET['lms_origin'])) {
    //     // Moodle might have refered, try to get it from referer.
    //     $referer = parse_url($_SERVER['HTTP_REFERER']);
    //     $query = array();
    //     parse_str($referer['query'], $query);
    //     if (!isset($query['lms_origin'])) {
    //         // It's NOT coming remotely so login.
    //         require_login($course, true, $cm);
    //     } else {
    //         // We found the client domain, set it to GET so we can use it in recursive call.
    //         $_GET['lms_origin'] = $query['lms_origin'];
    //         return scormremote_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
    //     }
    // } else {
    //     // And authorise.
    //     $clienthostname = filter_var($_GET['lms_origin'], FILTER_SANITIZE_URL);
    //     $clientconfig = \mod_scormremote\client_config::get_records_by_domain_and_scormremoteid($clienthostname, $cm->instance);
    //     if (!$clientconfig->get('id')) {
    //         die(403);
    //     }
    // }

    $canmanageactivity = has_capability('moodle/course:manageactivities', $context);
    $lifetime = null;

    if ($filearea === 'content') {
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_scormremote/content/0/$relativepath";
        $options['immutable'] = true; // Add immutable option, $relativepath changes on file update.

    } else if ($filearea === 'remote') {
        // From the manifest we get the data-source taget by identifier.
        $datasource = \moodle_url::make_pluginfile_url(
            $context->id,
            'mod_scormremote',
            'content',
            null,
            '/',
            implode('/', $args) // The original file path.
        );

        $templatedata = [
            'datasource'       => $datasource,
            'jssource'         => $CFG->wwwroot . '/mod/scormremote/amd/src/layer3.js',
            'scormagainsource' => $CFG->wwwroot . '/mod/scormremote/scorm-again/dist/scorm12.min.js',
        ];

        echo $OUTPUT->render_from_template('mod_scormremote/thirdlayer', $templatedata);
        die;
    } else if ($filearea === 'package' || $filearea === \mod_scormremote\wrapper::FILEAREA) {
        // Check if the global setting for disabling package downloads is enabled.
        if (!$canmanageactivity) {
            return false;
        }
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_scormremote/$filearea/$revision/$relativepath";
        $lifetime = 0; // No caching here.
    } else if ($filearea === 'imsmanifest') { // This isn't a real filearea, it's a url parameter for this type of package.
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);

        // Get imsmanifest file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_scormremote', 'package', 0, '', false);
        $file = reset($files);

        // Check that the package file is an imsmanifest.xml file - if not then this method is not allowed.
        $packagefilename = $file->get_filename();
        if (strtolower($packagefilename) !== 'imsmanifest.xml') {
            return false;
        }

        $file->send_relative_file($relativepath);
    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        if ($filearea === 'content') { // Return file not found straight away to improve performance.
            send_header_404();
            die;
        }
        return false;
    }

    // Finally send the file.
    send_stored_file($file, $lifetime, 0, false, $options);
}