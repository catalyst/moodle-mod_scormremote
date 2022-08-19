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

use \mod_scormremote\client;
use \mod_scormremote\client_config;
use \mod_scormremote\form\client_config as client_config_form;

$BASEURL = '/mod/scormremote/clientconfig.php';

// Parameters.
$cmid     = required_param('cmid', PARAM_INT); // Must have cmid.
$ccid     = optional_param('id', null, PARAM_INT); // Client Config id.
$editing  = optional_param('editingon', false, PARAM_BOOL);
$deleting = optional_param('deleting', false, PARAM_BOOL);
$delete   = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$cm     = get_coursemodule_from_id('scormremote', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$scormremote   = $DB->get_record('scormremote', array('id' => $cm->instance), '*', MUST_EXIST);
$contextmodule = context_module::instance($cm->id);

require_login($course, false, $cm);
$PAGE->set_url(new moodle_url($BASEURL, ['cmid' => $cmid]));

$PAGE->set_title("$course->shortname: ".format_string($scormremote->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('clientconfig', 'mod_scormremote'), new moodle_url($BASEURL, ['cmid' => $cmid]));

// Instatiate a client config object if we recieved an CCID.
$clientconfig = null;
if (!empty($ccid)) {
    $clientconfig = new client_config($ccid);
}

// Handling create or update.
if ($editing) {
    // Create the form instance.
    $customdata = [
        'persistent' => $clientconfig,
        'clientconfigid' => $ccid,
        'userid' => $USER->id,
        'scormremoteid' => $scormremote->id,
    ];
    $form = new client_config_form(new moodle_url($BASEURL, ['id' => $ccid, 'cmid' => $cmid, 'editingon' => 1]), $customdata);

    if ($data = $form->get_data()) {
        try {
            if (empty($data->id)) {
                // Create a new record.
                $clientconfig = new client_config(0, $data);
                $clientconfig->create();
            } else {
                // Update a record.
                $clientconfig->from_record($data);
                $clientconfig->update();
            }
            \core\notification::success(get_string('changessaved'));
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect(new moodle_url($BASEURL, ['cmid' => $cmid]));
    }
}

// Handling delete.
if ($deleting && $ccid && $clientconfig && $delete === md5($clientconfig->get('id'))) {
    try {
        $clientconfig->delete();
        \core\notification::success(get_string('manage_clientdeletesuccess', 'mod_scormremote'));
    } catch (Exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect(new moodle_url($BASEURL, ['cmid' => $cmid]));
}

// Handling read. Only do this when !$editing and !$deleting.
if (!$editing && !$deleting) {
    $clientconfigs = client_config::get_records(['scormremoteid' => $scormremote->id]);

    // Create a table, with three colums; client, download package, actions.
    $table = new html_table();
    $table->head = [
        get_string('client', 'mod_scormremote'),
        get_string('seats', 'mod_scormremote'),
        get_string('seatsinuse', 'mod_scormremote'),
        get_string('wrappedpackagefile', 'mod_scormremote'),
        get_string('actions'),
    ];

    $editicon = $OUTPUT->pix_icon('i/settings', get_string('edit'));
    $deleteicon = $OUTPUT->pix_icon('i/delete', get_string('delete'));

    foreach ($clientconfigs as $config) {
        $client = new client($config->get('clientid'));

        // Create action edit and delete.
        $editurl = new moodle_url($BASEURL, ['cmid' => $cmid, 'id' => $config->get('id'), 'editingon' => 1]);
        $editaction = html_writer::link($editurl, $editicon);
        $deleteurl =  new moodle_url($BASEURL, ['cmid' => $cmid, 'id' => $config->get('id'), 'deleting' => 1]);
        $deleteaction = html_writer::link($deleteurl, $deleteicon);

        // Download link for wrapper.
        $download = html_writer::link(
            new moodle_url('/mod/scormremote/download.php', ['cmid' => $cmid, 'clientid' => $client->get('id')]),
            get_string('download')
        );

        $table->data[] = [
            "{$client->get('name')} ({$client->get('domain')})",
            $config->get('maxseatcount'),
            'unknown',
            $download,
            $editaction . $deleteaction,
        ];
    }
}

// Start of output.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($scormremote->name));

$currenttab = 'clientconfig';
require($CFG->dirroot . '/mod/scormremote/tabs.php');
if($editing && $ccid == null) {
    // Creating.
    echo $OUTPUT->heading(get_string('manage_clientconfigcreateheader', 'mod_scormremote'), 4);
    $form->display();

} else if ($editing && $ccid) {
    // Updating.
    echo $OUTPUT->heading(get_string('manage_clientconfigupdateheader', 'mod_scormremote'), 4);
    $form->display();

} else if ($deleting && $ccid && $clientconfig) {
    // Deleting.
    // This is showing a confimation box, no header here.
    $message = get_string('manage_clientconfigdeletemessage', 'mod_scormremote');

    $confirmurl = new moodle_url($BASEURL, ['cmid' => $cmid, 'id' => $ccid, 'deleting' => 1, 'delete' => md5($clientconfig->get('id'))]);
    $confirmbtn = new single_button($confirmurl, get_string('delete'), 'post');
    echo $OUTPUT->confirm($message, $confirmbtn, new moodle_url($BASEURL, ['cmid' => $cmid]));

} else {
    // Reading.
    $createnewurl = new moodle_url($BASEURL, ['cmid' => $cmid, 'editingon' => 1]);
    echo html_writer::table($table);
    echo $OUTPUT->single_button($createnewurl, get_string('manage_clientconfig', 'mod_scormremote'));
}

echo $OUTPUT->footer();
