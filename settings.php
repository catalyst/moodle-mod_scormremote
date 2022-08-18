<?php


defined('MOODLE_INTERNAL') || die;

$category = new admin_category('modscormremotecat', get_string('pluginname', 'mod_scormremote'));

$general = new admin_settingpage('modsettingscormremote', get_string('generalsettings', 'admin'), 'moodle/site:config');
$clientslink = new admin_externalpage(
    'scormremoteclients',
    get_string('manage_clients', 'mod_scormremote'),
    new moodle_url('/mod/scormremote/clients.php')
);
$ADMIN->add('modsettings', $category);
$ADMIN->add('modscormremotecat', $general);
$ADMIN->add('modscormremotecat', $clientslink);

$settings = null;
