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
 * Scormremote settings.
 *
 * @package    mod_scormremote
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$category = new admin_category('modscormremotecat', get_string('pluginname', 'mod_scormremote'));

$general = new admin_settingpage('modsettingscormremote', get_string('generalsettings', 'admin'), 'moodle/site:config');
$clientslink = new admin_externalpage(
    'scormremoteclients',
    get_string('manage_clients', 'mod_scormremote'),
    new moodle_url('/mod/scormremote/clients.php')
);
$tierslink = new admin_externalpage(
    'scormremotetiers',
    get_string('manage_tiers', 'mod_scormremote'),
    new moodle_url('/mod/scormremote/tiers.php')
);
$ADMIN->add('modsettings', $category);
$ADMIN->add('modscormremotecat', $general);
$ADMIN->add('modscormremotecat', $clientslink);
$ADMIN->add('modscormremotecat', $tierslink);

$settings = null;


$options = get_default_enrol_roles(context_system::instance());
$student = get_archetype_roles('student');
$student = reset($student);
$general->add(new admin_setting_configselect('mod_scormremote/roleid',
    get_string('defaultrole', 'role'), '', $student->id ?? null, $options));

$choices = [
    0 => get_string('logdebug', 'mod_scormremote'),
    1 => get_string('loginfo', 'mod_scormremote'),
    2 => get_string('logwarn', 'mod_scormremote'),
    3 => get_string('logerror', 'mod_scormremote'),
    4 => get_string('lognone', 'mod_scormremote'),
    ];

$general->add(new admin_setting_configselect('mod_scormremote/debugloglevel',
    get_string('debugloglevel', 'mod_scormremote'),
    get_string('debuglogleveldescription', 'mod_scormremote'), 3, $choices));
