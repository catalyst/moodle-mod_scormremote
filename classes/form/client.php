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
 * This file contains the form add/update a scormremote client.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client extends \core\form\persistent {

    /** @var string persistent class name. */
    protected static $persistentclass = 'mod_scormremote\\client';

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('manage_clientname', 'mod_scormremote'), 'maxlength="100"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'server');
        $mform->addRule('name', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        $mform->addElement('text', 'domain', get_string('manage_clientdomain', 'mod_scormremote'), 'maxlength="100"');
        $mform->setType('domain', PARAM_TEXT);
        $mform->addRule('domain', get_string('required'), 'required', null, 'server');
        $mform->addRule('domain', get_string('maximumchars', '', 253), 'maxlength', 253, 'server');
        $mform->addHelpButton('domain', 'domain', 'mod_scormremote');

        $savetext = get_string('savechanges');

        if (!$this->get_persistent()->get('id')) {
            $savetext = get_string('add');
        }
        $this->add_action_buttons(true, $savetext);
    }
}
