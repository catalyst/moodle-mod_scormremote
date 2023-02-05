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

use coding_exception;

/**
 * This file contains the form add/update a scormremote client.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wrapper extends \moodleform {
    /** Instance name. */
    const MODULE_INSTANCE_NAME = 0;

    /** Instance reference. */
    const MODULE_INSTANCE_REFERENCE = 1;

    /** Other. */
    const OTHER = 2;

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        if (!isset($this->_customdata['name']) || !isset($this->_customdata['reference'])) {
            throw new coding_exception('Misssing customdata for form');
        }

        // Radio buttons.
        $radioarray = [
            $mform->createElement('radio', 'filename', null, $this->_customdata['name'], self::MODULE_INSTANCE_NAME),
            $mform->createElement('radio', 'filename', null, $this->_customdata['reference'], self::MODULE_INSTANCE_REFERENCE),
            $mform->createElement('radio', 'filename', null, get_string('other'), self::OTHER),
        ];
        $mform->addGroup($radioarray, 'filenamegroup', get_string('filename', 'mod_scormremote'), '</br>');
        $mform->setDefault('filename', self::MODULE_INSTANCE_NAME);

        // Other text input field.
        $mform->addElement('text', 'filenameother', get_string('filenameother', 'mod_scormremote'));
        $mform->setType('filenameother', PARAM_RAW);
        $mform->addHelpButton('filenameother', 'filenameother', 'mod_scormremote');

        $this->add_action_buttons(false, get_string('download'));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array Error messages
     */
    public function validation($data, $files) {
        $errors = [];
        $filename = (int)$data['filenamegroup']['filename'];
        if (!empty($filename) && $filename === self::OTHER) {
            $regex = '/^[\w\s.-]*$/';
            if (empty($data['filenameother'])) {
                $errors['filenameother'] = get_string('filenameother_error', 'mod_scormremote');
            } else if (!preg_match($regex, $data['filenameother'])) {
                $errors['filenameother'] = get_string('filenameother_error', 'mod_scormremote');
            }
        }

        return $errors;
    }
}
