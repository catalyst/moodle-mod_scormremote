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

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('domains', 'tiers');

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        $updating = !!$this->get_persistent()->get('id');

        $mform->addElement('text', 'name', get_string('manage_clientname', 'mod_scormremote'), 'maxlength="100"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'server');
        $mform->addRule('name', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        $mform->addElement('textarea', 'domains', get_string("manage_clientdomain", "mod_scormremote"), 'wrap="virtual" rows="5" cols="50"');
        $mform->addHelpButton('domains', 'domain', 'mod_scormremote');
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
        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('none'),
        );
        $mform->addElement('autocomplete', 'tiers', get_string('subs', 'scormremote'), $tiers, $options);
        $mform->setDefault('tiers', $this->_customdata['tiers']);

        $this->add_action_buttons(true, $savetext);
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
        $domains = utils::textarea_to_string_array($data->domains);
        $clientid = $this->get_persistent()->get('id');

        foreach ($domains as $domain) {
            if (!\core\ip_utils::is_domain_name($domain)) {
                $newerrors['domains'] = get_string('error_clientdomainnotvalid', 'mod_scormremote', $domain);
                return $newerrors;
            }

            // Check if the domain is in use by a different client.
            $exists = client_domain::get_record(['domain' => $domain]);
            if ($exists && $exists->get('clientid') != $clientid) {
                $newerrors['domains'] = get_string('error_clientdomainnotunique', 'mod_scormremote', $domain);
                return $newerrors;
            }
        }

        return $newerrors;
    }
}
