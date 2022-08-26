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
 * Prints an instance of mod_scormremote.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$s = optional_param('s', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('scormremote', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $scormremote = $DB->get_record('scormremote', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $scormremote = $DB->get_record('scormremote', array('id' => $s), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $scormremote->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('scormremote', $scormremote->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/scormremote/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($scormremote->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($scormremote->name));

$currenttab = 'info';
require($CFG->dirroot . '/mod/scormremote/tabs.php');

$url = moodle_url::make_pluginfile_url($modulecontext->id, 'mod_scormremote', 'remote', 0, '/', 'index.html', false);
echo html_writer::link($url, get_string('enter', 'scorm'), ['class' => 'btn btn-primary', 'target' => '_blank']);
echo $OUTPUT->footer();
