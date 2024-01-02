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
 * Display information about all the mod_scormremote modules in the requested course.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_scormremote\client;
use \mod_scormremote\utils;

// No login check is expected here because this is accessed from external LMS and
// all required checks are performed before updating completion info.
// @codingStandardsIgnoreLine
require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

$contextid = required_param('contextid', PARAM_INT);
$origin    = required_param('lms_origin', PARAM_URL);
$username  = required_param('student_id', PARAM_RAW_TRIMMED);
$clientid  = optional_param('client_id', '', PARAM_TEXT);

$client = client::get_record_by_domain($origin, $clientid);
if (!$client) {
    exit('NOT OK');
}

$context = \context::instance_by_id($contextid);
$cm = get_coursemodule_from_id('scormremote', $context->instanceid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$sub = $client->get_subscription_by_courseid($course->id);
if (!$sub) {
    exit('NOT OK');
}

$user = utils::get_user($client, $username);
if (!$user || !is_enrolled($context, $user)) {
    exit('NOT OK');
}

$completion = new \completion_info($course);
$completion->update_state($cm, COMPLETION_COMPLETE, $user->id);
exit('OK');
