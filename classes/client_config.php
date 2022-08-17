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

namespace mod_scormremote;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for scormremote client configurations. This holds the connection between client and course modules. Each configuration
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_config extends \core\persistent {
    /** Database table. */
    const TABLE = 'scormremote_client_configs';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'clientid' => array(
                'description' => 'The client id to which this configuration is linked.',
                'type' => PARAM_INT
            ),
            'scormremoteid' => array(
                'description' => 'The scormremote module instance id linked to this config.',
                'type' => PARAM_INT
            ),
            'maxseatcount' => array(
                'default' => 0,
                'description' => 'The number of seats open for allocation for this config.',
                'type' => PARAM_INT
            ),
        );
    }

    /**
     * Validate a clientid.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_clientid($value) {
        if (!client::record_exists($value)) {
            return new \lang_string('error_clientconfigclientnotfound', 'mod_scormremote', $value);
        }

        return true;
    }

    /**
     * Validate a scormremoteid.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_scormremoteid($value) {
        global $DB;
        if (!$DB->get_record('scormremote', array('id' => $value), 'id')){
            return new \lang_string('error_clientconfigscormremotenotfound', 'mod_scormremote', $value);
        }

        return true;
    }

    /**
     * Validate a maxseatcount.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_maxseatcount($value) {
        if ($value < 0) {
            return new \lang_string('error_clientconfigmaxseatcounttolow', 'mod_scormremote');
        }

        // TODO: Must check that what is going to be set is lower than the already allocated searts.
        return true;
    }
}