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
            'expiry' => array(
                'type' => PARAM_INT,
                'description' => 'The expiry date of the client.',
                'default' => 0,
            ),
            'primarydomain' => array(
                'type' => PARAM_TEXT,
                'description' => 'The primary domain of the client.',
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
     * Get the client that does not have a subscription.
     *
     * @return client[]
     */
    public static function get_records_without_subscription() {
        global $DB;

        $sql = "SELECT client.*
                  FROM {scormremote_clients} client
                 WHERE NOT EXISTS (
                           SELECT 1
                             FROM {scormremote_subscriptions} sub
                            WHERE client.id = sub.clientid
                       )
              ORDER BY client.name";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    /**
     * Get client by domain.
     *
     * @param string $domain
     * @param string $clientid Optional to restrict access to scorm if domain is used by more than one client.
     * @return client
     * @throws \dml_exception
     */
    public static function get_record_by_domain(string $domain, string $clientid) {
        global $DB;

        $clientidclause = ($clientid) ? "client.id = :clientid" : "1 = 1";

        $sql = "SELECT client.*
                  FROM {scormremote_clients} client
                 WHERE client.id IN (
                           SELECT scd.clientid
                             FROM {scormremote_client_domains} scd
                            WHERE scd.domain = :domain1
                            UNION
                           SELECT sc.id AS clientid
                             FROM {scormremote_clients} sc
                            WHERE sc.primarydomain = :domain2
                       )
                   AND $clientidclause";

        $record = $DB->get_record_sql($sql, ['domain1' => $domain, 'domain2' => $domain, 'clientid' => $clientid]);

        if (!$record) {
            return null;
        }

        return new static(0, $record);
    }

    /**
     * Get clients with subscription by course id.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_clients_by_courseid(int $courseid) {
        global $DB;

        $sql = "SELECT sc.*
                  FROM {scormremote_clients} sc
                  JOIN {scormremote_subscriptions} ss ON ss.clientid = sc.id
                  JOIN {scormremote_course_tiers} sct ON sct.tierid = ss.tierid
                 WHERE sct.courseid = :courseid";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql, ['courseid' => $courseid]);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    /**
     * Returns the subscription to which the courseid is a part of. This can only be one.
     *
     * @param int $courseid
     * @return subsciption|null
     */
    public function get_subscription_by_courseid(int $courseid) {
        global $DB;

        $sql = "SELECT sub.*
                  FROM {scormremote_subscriptions} sub
                  JOIN {scormremote_course_tiers} ct
                    ON sub.tierid = ct.tierid
                   AND ct.courseid = :courseid
                 WHERE sub.clientid = :clientid";

        $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'clientid' => $this->get('id')]);

        if (!$record) {
            return null;
        }

        return new subscription(0, $record);
    }

    /**
     * Return boolean value. It checks if this client has at least one subscription.
     *
     * @return bool
     */
    public function has_subscription() {
        return subscription::count_records(['clientid' => $this->get('id')]) > 0;
    }

    /**
     * Return true if given courseid is in subscription.
     *
     * @param int $courseid
     * @return boolean
     */
    public function is_course_in_subscription(int $courseid) {
        global $DB;

        $sql = "SELECT COUNT(*)
                  FROM {scormremote_subscriptions} sub
                  JOIN {scormremote_course_tiers} ct
                    ON sub.tierid = ct.tierid
                   AND ct.courseid = :courseid
                 WHERE sub.clientid = :clientid";

        return $DB->count_records_sql($sql, ['courseid' => $courseid, 'clientid' => $this->get('id')]) > 0;
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
