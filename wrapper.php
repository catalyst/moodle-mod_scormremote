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
 * This page is for managing scormremote client configurations.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

$baseurl = '/mod/scormremote/wrapper.php';

// Parameters.
$cmid     = required_param('cmid', PARAM_INT); // Must have cmid.
$editing  = optional_param('editingon', false, PARAM_BOOL);
$deleting = optional_param('deleting', false, PARAM_BOOL);
$delete   = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

// Instances.
$cm     = get_coursemodule_from_id('scormremote', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$scormremote   = $DB->get_record('scormremote', array('id' => $cm->instance), '*', MUST_EXIST);
$contextmodule = context_module::instance($cm->id);

// Authenticate & Authorize.
require_login($course, false, $cm);
require_capability('mod/scormremote:downloadwrapper', $contextmodule);

$PAGE->set_url(new moodle_url($baseurl, ['cmid' => $cmid]));
$PAGE->set_title("$course->shortname: ".format_string($scormremote->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('wrapper', 'mod_scormremote'), new moodle_url($baseurl, ['cmid' => $cmid]));

$customdata = [
    'name' => $scormremote->name,
    'reference' => $scormremote->reference,
    'courseid' => $course->id,
];
$form = new \mod_scormremote\form\wrapper(new moodle_url($baseurl, ['cmid' => $cmid, 'editingon' => 1]), $customdata);

if ($data = $form->get_data()) {
    switch ((int)$data->filenamegroup['filename']) {
        case \mod_scormremote\form\wrapper::OTHER:
            $filename = $data->filenameother;
            break;
        case \mod_scormremote\form\wrapper::MODULE_INSTANCE_NAME:
            $filename = $scormremote->name;
            break;
        default: // Defaults to the orginal package name.
            $filename = $scormremote->reference;
            break;
    }

    // Process client id and file name for client specific wrapper.
    $clientid = null;
    if ($data->clients) {
        $clientid = $data->clients[0];
        $client = new \mod_scormremote\client($clientid);

        // Return filename part without .zip which may or may not be added if provided by user.
        // Add the client name and .zip extension.
        preg_match('/(.*?)(\.zip)*?$/', $filename, $matches);
        $filename = $matches[1] . ' - for ' . $client->get('name') . '.zip';

    }


    // Make sure the filename is *.zip.
    if (strlen($filename) < 4 || substr($filename, -4) != '.zip') {
        $filename .= '.zip';
    }

    \mod_scormremote\packagefile::download_wrapper($scormremote, $filename, $clientid);
}

// Start of output.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($scormremote->name));

$currenttab = 'wrapper';
require($CFG->dirroot . '/mod/scormremote/tabs.php');

echo $OUTPUT->heading(get_string('scormremote:downloadwrapper', 'mod_scormremote'), 4);
$form->display();

echo $OUTPUT->footer();
