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

use mod_scormremote\client_domain;
use mod_scormremote\utils;

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

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('domains', 'tiers', 'mform_isexpanded_id_clientdetails',
        'mform_isexpanded_id_alloweddomains', 'mform_isexpanded_id_subscriptions');

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $updating = !!$this->get_persistent()->get('id');

        $mform->addElement('header', 'clientdetails', get_string('manage_clientdetails', 'mod_scormremote'));

        $mform->addElement('text', 'name', get_string('manage_clientname', 'mod_scormremote'), 'maxlength="100"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'server');
        $mform->addRule('name', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        $mform->addElement('text', 'primarydomain', get_string('manage_primaryclientdomain', 'mod_scormremote'), 'maxlength="255"');
        $mform->setType('primarydomain', PARAM_TEXT);
        $mform->addRule('primarydomain', get_string('required'), 'required', null, 'server');
        $mform->addRule('primarydomain', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');
        $mform->addHelpButton('primarydomain', 'domain', 'mod_scormremote');

        $mform->addElement('date_time_selector', 'expiry', get_string('expiry', 'mod_scormremote'), array('optional' => true));

        $mform->addElement('header', 'alloweddomains', get_string('manage_alloweddomains', 'mod_scormremote'));

        $domainoptions = array(
            'multiple' => true,
            'noselectionstring' => get_string('none'),
            'tags' => true,
            'placeholder' => get_string('manage_adddomain', 'mod_scormremote'),
        );
        $mform->addElement('autocomplete', 'domains', get_string('manage_additionalclientdomain',
            'mod_scormremote'), array(), $domainoptions);
        $mform->addElement('static', 'domains_desc', '', get_string('manage_domains_desc',
            'mod_scormremote'));

        $mform->setDefault('domains', $this->_customdata['domains']);

        $savetext = get_string('savechanges');
        if (!$updating) {
            $savetext = get_string('add');
        }

        $tierrecords = \mod_scormremote\tier::get_records([], $sort = 'seats');
        $tiers = array();
        foreach ($tierrecords as $tier) {
            $key   = $tier->get('id');
            $value = "{$tier->get('name')} ( {$tier->get('seats')} seats )";
            $tiers[$key] = $value;
        }

        $mform->addElement('header', 'subscriptions', get_string('manage_subscriptions', 'scormremote'));

        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('none'),
        );
        $mform->addElement('autocomplete', 'tiers', get_string('subs', 'scormremote'), $tiers, $options);
        $mform->setDefault('tiers', $this->_customdata['tiers']);

        $this->add_action_buttons(true, $savetext);

        $mform->setExpanded('clientdetails', true);

        // Expand allowed domains if the list is not empty.
        $expandalloweddomains = (bool)count($this->_customdata['domains']);
        $mform->setExpanded('alloweddomains', $expandalloweddomains);
        $mform->setExpanded('subscriptions', true);

    }

    /**
     * Extra validation.
     *
     * @param  stdClass $data Data to validate.
     * @param  array $files Array of files.
     * @param  array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        $newerrors = array();
        $domains = $data->domains;
        $clientid = $this->get_persistent()->get('id');

        if (!\core\ip_utils::is_domain_name($data->primarydomain)) {
            $newerrors['primarydomain'] = get_string('error_clientdomainnotvalid', 'mod_scormremote', $data->primarydomain);
            return $newerrors;
        }

        foreach ($domains as $domain) {
            if (!\core\ip_utils::is_domain_name($domain)) {
                $newerrors['domains'] = get_string('error_clientdomainnotvalid', 'mod_scormremote', $domain);
                return $newerrors;
            }
        }

        if (count($data->tiers) > 1) {
            global $DB;
            // Get all the tiers.
            $courses = array();
            foreach ($data->tiers as $tierid) {
                $set = $DB->get_fieldset_select('scormremote_course_tiers', 'courseid',
                    'tierid = :tierid', ['tierid' => $tierid]);
                if (array_intersect($courses, $set)) {
                    $newerrors['tiers'] = get_string('error_coursesnotunique', 'mod_scormremote');
                    return $newerrors;
                }
                $courses = array_merge($courses, $set);
            }
        }

        return $newerrors;
    }
}
