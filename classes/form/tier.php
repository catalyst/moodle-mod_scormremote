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

/**
 * This file contains the form add/update a scormremote tier.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tier extends \core\form\persistent {

    /** @var string persistent class name. */
    protected static $persistentclass = 'mod_scormremote\\tier';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('courses');

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $updating = !!$this->get_persistent()->get('id');

        $mform->addElement('text', 'name', get_string('manage_tiername', 'mod_scormremote'), 'maxlength="100"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'server');
        $mform->addRule('name', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        $mform->addElement('text', 'seats', get_string('manage_tierseats', 'mod_scormremote'));
        $mform->setType('seats', PARAM_INT);
        $mform->addRule('seats', get_string('required'), 'required', null, 'server');
        $mform->setDefault('seats', 0);

        $mform->addElement('textarea', 'description', get_string('manage_tierdescription', 'mod_scormremote'),
             'wrap="virtual" rows="5" cols="50"');

        $courserecords = get_courses('all', $sort = 'c.shortname ASC', 'c.id, c.shortname');
        $courses = array();
        foreach ($courserecords as $course) {
            $courses[(int)$course->id] = $course->shortname;
        }
        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('none'),
        );
        $mform->addElement('autocomplete', 'courses', get_string('courses'), $courses, $options);
        $mform->setDefault('courses', $this->_customdata['courses']);

        $savetext = get_string('savechanges');
        if (!$updating) {
            $savetext = get_string('add');
        }
        $this->add_action_buttons(true, $savetext);
    }
}
