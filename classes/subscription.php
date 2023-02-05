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
     * Get records by client id.
     *
     * @param int $clientid The client id.
     * @return subscription[]
     */
    public static function get_records_by_clientid(int $clientid): array {
        return static::get_records(['clientid' => $clientid]);
    }

    /**
     * Returns the users objects of users in that are using this subscription for access.
     *
     * @return int
     */
    public function get_participant_count() {
        global $DB;

        $sql = "SELECT COUNT(*) as taken
                  FROM (
                        SELECT DISTINCT usr.id
                          FROM {user} usr                      -- from users
                          JOIN {user_enrolments} usr_enr       -- select all enrolments
                            ON usr_enr.userid = usr.id
                          JOIN {enrol} enr                     -- select course enrolment
                            ON enr.id = usr_enr.enrolid
                          JOIN {scormremote_course_tiers} ct
                            ON enr.courseid = ct.courseid        -- join tiers which are connected to the course
                          JOIN {scormremote_subscriptions} sub
                            ON sub.tierid = ct.tierid            -- the client must be subscribed to the tier
                         WHERE usr.deleted = 0                   -- dont't select deleted users
                           AND usr.username LIKE :usernamewildcard
                           AND sub.id = :subscriptionid
                       ) temp";

        return $DB->count_records_sql($sql, [
            'subscriptionid'   => $this->get('id'),
            'usernamewildcard' => $DB->sql_like_escape("enrol_scormremote_{$this->get('clientid')}_") . "%",
        ]);
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
     * Delete all entries where tierid equals to given tier id.
     *
     * @param int $tierid The tier id.
     * @return null
     */
    public static function delete_by_tier($tierid) {
        global $DB;
        return $DB->delete_records(self::TABLE, array('tierid' => $tierid));
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
