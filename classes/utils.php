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
 * Class contains static utilitarion methods.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Return the imsmanifest as string uploaded to given scormremote instance.
     *
     * @param object $scormremote instance
     * @return string
     */
    public static function get_scormremote_imsmanifest(&$scormremote) {
        // Get the cm for context.
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        $context = \context_module::instance($scormremote->coursemodule);

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_scormremote', 'content', 0, '/', 'imsmanifest.xml');

        // This should not happen.
        if (!$file) {
            throw new \moodle_exception('error_imsmanifestmissing', 'mod_scormremote');
        }

        return $file->get_content();
    }

    /**
     * Function to get course module context given a scormremote context. It will set $scoremote->coursemodule to the course module
     * id.
     *
     * @param object $scormremote instance
     * @return \context_module
     */
    public static function get_context(&$scormremote) {
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        return \context_module::instance($scormremote->coursemodule);
    }

    /**
     * Retrieve a string[] from textarea input which is seperated by new lines.
     *
     * @param string $value
     * @return string[]
     */
    public static function textarea_to_string_array($value) {
        $lines = array();

        if (empty($value)) {
            return $lines;
        }

        $linesraw = explode(PHP_EOL, trim($value));

        foreach ($linesraw as $lineraw) {
            $line = trim($lineraw); // Get rid of \r.
            if (empty($line)) {
                continue;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Given a fullname, extract to firstname and lastname.
     *
     * @param string $fullname
     * @return array
     */
    public static function fullname_to_first_and_lastname(string $fullname) {
        $firstname = null;
        $lastname  = null;

        $firstspace = strpos($fullname, ' ');

        if ($firstspace === false) {
            if (strlen($fullname) > 0) {
                $firstname = $fullname;
            }
            return [$firstname, $lastname];
        }

        $firstname = substr($fullname, 0, $firstspace);
        $lastname = substr($fullname, $firstspace + 1);

        return [$firstname, $lastname];
    }

    /**
     * Transform passed username to client specific username.
     *
     * @param client $client
     * @param string $username
     * @return string
     */
    public static function transform_username(client $client, string $username) {
        return 'enrol_scormremote_' . $client->get('id') . '_' . sha1($client->get('id') . '_' . $username);
    }

    /**
     * Return a client user object from username.
     *
     * @param string $username
     * @return \stdClass|bool
     */
    public static function get_user(client $client, string $username) {
        global $DB;
        return $DB->get_record('user', ['username' => static::transform_username($client, $username)]);
    }

    /**
     * Create a user for a client.
     *
     * @param client $client
     * @param string $username
     * @param string $fullname
     * @return \stdClass|bool
     */
    public static function create_user(client $client, string $username, string $fullname) {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        [$firstname, $lastname] = utils::fullname_to_first_and_lastname($fullname);

        $user = new \stdClass();
        $user->username = static::transform_username($client, $username);
        $user->email = $user->username . '@example.com';
        $user->auth = 'nologin';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->firstname = $firstname ?? $username;
        $user->lastname = $lastname ?? $client->get('name');
        $user->password = '';
        $user->confirmed = 1;
        $user->id = user_create_user($user, false);
        return (object) get_complete_user_data('id', $user->id);
    }

    /**
     * Return integer value of the total seats occupied in this course by enrolments which belong to client.
     *
     * @param int $courseid
     * @param int $clientid
     * @return int
     */
    public static function seats_taken_in_course($courseid, $clientid) {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT usr.*)
                  FROM {user} usr
                  JOIN {user_enrolments} usr_enr
                    ON usr_enr.userid = usr.id
                  JOIN {enrol} enr
                    ON enr.id = usr_enr.enrolid
                  JOIN {scormremote_course_tiers} ct
                    ON enr.courseid = :courseid
                  JOIN {scormremote_subscriptions} sub
                    ON sub.tierid = ct.tierid
                 WHERE usr.deleted = 0
                   AND usr.username LIKE CONCAT('enrol_scormremote_', sub.clientid, '_%')
                   AND sub.clientid = :clientid";

        return $DB->count_records_sql($sql, ['courseid' => $courseid, 'clientid' => $clientid]);
    }

    /**
     * Show a total count of seats that are available for client in course.
     *
     * @param int $courseid
     * @param int $clientid
     * @return int
     */
    public static function seats_available_in_course(int $courseid, int $clientid) {
        global $DB;

        $sql = "SELECT SUM(t.seats) AS avail
                  FROM {scormremote_tiers} t
                  JOIN {scormremote_course_tiers} ct
                    ON ct.tierid = t.id
                   AND ct.courseid = :courseid
                  JOIN {scormremote_subscriptions} sub
                    ON sub.tierid = t.id
                   AND sub.clientid = :clientid";

        return $DB->count_records_sql($sql, ['courseid' => $courseid, 'clientid' => $clientid]);
    }
}