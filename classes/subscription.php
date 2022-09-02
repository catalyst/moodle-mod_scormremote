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
 * Class for loading/storing scormremote subscription from the DB.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription extends \core\persistent {
    /** Database table. */
    const TABLE = 'scormremote_subscriptions';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'clientid' => array(
                'type' => PARAM_INT,
                'description' => 'The id of the client.',
            ),
            'tierid' => array(
                'type' => PARAM_INT,
                'description' => 'The id of the tier.'
            ),
        );
    }

    /**
     * Delete all entries where clientid equals to given client id.
     *
     * @param int $clientid The client id.
     * @return null
     */
    public static function delete_by_client($clientid) {
        global $DB;
        return $DB->delete_records(self::TABLE, array('clientid' => $clientid));
    }

    /**
     * Validate a clientid.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_clientid($value) {
        if (!client::record_exists($value)) {
            return new \lang_string('error_clientnotfound', 'mod_scormremote', $value);
        }

        return true;
    }

    /**
     * Validate a tierid.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_tierid($value) {
        if (!tier::record_exists($value)) {
            return new \lang_string('error_tiernotfound', 'mod_scormremote', $value);
        }

        return true;
    }
}