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
 * This page allows a user to download a client specific wrapper.
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
use \mod_scormremote\wrapper;

$BASEURL = '/mod/scormremote/download.php';

// Parameters.
$cmid          = required_param('cmid', PARAM_INT);
$clientid      = required_param('clientid', PARAM_INT);

// Create objects.
$cm          = get_coursemodule_from_id('scormremote', $cmid, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$scormremote = $DB->get_record('scormremote', array('id' => $cm->instance), '*', MUST_EXIST);
$context     = context_module::instance($cm->id);

// Authentication and authorization.
require_login($course, false, $cm);
require_capability('moodle/course:manageactivities', $context);
$PAGE->set_url(new moodle_url($BASEURL, ['cmid' => $cmid, 'clientid' => $clientid]));

// Create client config.
$client = new client($clientid);
$clientconfig = client_config::get_record(['scormremoteid' => $cm->instance, 'clientid' => $clientid]);
if (!$clientconfig) {
    send_header_404();
    die;
}

// Create a wrapper.
$file = wrapper::create($scormremote, $clientid);
send_stored_file($file);
