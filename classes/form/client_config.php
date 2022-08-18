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


namespace mod_scormremote\form;

defined('MOODLE_INTERNAL') || die();

/**
 * This file contains the form add/update a scormremote client configuration.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_config extends \core\form\persistent {

    /** @var string persistent class name. */
    protected static $persistentclass = 'mod_scormremote\\client_config';

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        // SCORM Remote instance id.
        $mform->addElement('hidden', 'scormremoteid');
        $mform->setConstant('scormremoteid', $this->_customdata['scormremoteid']);

        $mform->addElement('text', 'maxseatcount', get_string('seats', 'mod_scormremote'));
        $mform->setType('maxseatcount', PARAM_INT);
        $mform->setDefault('maxseatcount', 0);


        // Get only clients that do not have a config for this scormremote instance.
        $clients = \mod_scormremote\client::get_records_not_configured_for_scormremote($this->_customdata['scormremoteid']);
        if ($this->get_persistent()->get('id')) {
            // Do append with the current one if we're updating.
            $client = new \mod_scormremote\client($this->get_persistent()->get('clientid'));
            $clients[] = $client;
        }

        $options = [];
        foreach ($clients as $client) {
            $options[$client->get('id')] = $client->get('name') . ' (' . $client->get('domain') . ')';
        }

        $mform->addElement('select', 'clientid', get_string('client', 'mod_scormremote'), $options);
        $mform->addRule('clientid', get_string('required'), 'required');

        $savetext = get_string('savechanges');

        if (!$this->get_persistent()->get('id')) {
            $savetext = get_string('add');
        }
        $this->add_action_buttons(true, $savetext);
    }
}
