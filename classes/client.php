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
 * Class for loading/storing scormremote client from the DB.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client extends \core\persistent {
    /** Database table. */
    const TABLE = 'scormremote_clients';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'name' => array(
                'type' => PARAM_TEXT,
                'description' => 'The name of the client.',
            ),
        );
    }

    /**
     * Do something right before the object is deleted from the database.
     *
     * @return void
     */
    protected function before_delete() {
        client_domain::delete_by_client($this->get('id'));
        subscription::delete_by_client($this->get('id'));
    }

    /**
     * Get the domains for this client.
     *
     * @return client_domain[]
     */
    public function get_domains() {
        return client_domain::get_records(['clientid' => $this->get('id')]);
    }

    /**
     * Get the client which is subscriped to given tier with tierid.
     *
     * @param int $tierid
     * @return client[]
     */
    public static function get_records_by_tierid(int $tierid) {
        global $DB;

        $sql = "SELECT client.*
                  FROM {scormremote_clients} client
                 WHERE EXISTS (
                           SELECT 1
                             FROM {scormremote_subscriptions} sub
                            WHERE client.id = sub.clientid
                              AND sub.tierid = :tierid
                       )
              ORDER BY client.name";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql, ['tierid' => $tierid]);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }


    /**
     * Validate a client name.
     *
     * A client name must follow these conditions:
     *  - must have under 100 characters in length; and
     *  - must have more than 2 characters (3 min) in lenght; and
     *
     * @param string $value
     * @return true|\lang_string
     */
    protected function validate_name(string $value) {
        $len = strlen($value);

        // Must be between 2 and 100 characters in length.
        if ($len <= 2 || $len > 100) {
            return new \lang_string('error_clientnamelength', 'mod_scormremote', $len);
        }

        return true;
    }
}