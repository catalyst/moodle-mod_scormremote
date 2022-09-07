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
 * Print the tabs.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (empty($scormremote)) {
    throw new \moodle_exception('cannotaccess', 'mod_scorm');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
}

$contextmodule = context_module::instance($cm->id);

$tabs = array();
$row = array();
$inactive = array();
$activated = array();

$row[] = new tabobject('info', "$CFG->wwwroot/mod/scormremote/view.php?id=$cm->id", get_string('info'));

if (has_capability('mod/scormremote:downloadwrapper', $contextmodule)) {
    $row[] = new tabobject(
        'wrapper',
        "$CFG->wwwroot/mod/scormremote/wrapper.php?cmid=$cm->id",
        get_string('wrapper', 'mod_scormremote')
    );
}

// This makes it so if only one tab, don't show tabs.
if (!($currenttab == 'info' && count($row) == 1)) {
    $tabs[] = $row;
}

print_tabs($tabs, $currenttab, $inactive, $activated);
