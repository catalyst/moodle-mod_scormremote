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
 * Class for loading/storing scormremote tier to course relation from the DB.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_tier extends \core\persistent {
    /** Database table. */
    const TABLE = 'scormremote_course_tiers';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'courseid' => array(
                'type' => PARAM_INT,
                'description' => 'The id of the course.',
            ),
            'tierid' => array(
                'type' => PARAM_INT,
                'description' => 'The id of the tier.'
            ),
        );
    }

    /**
     * Delete all entries where courseid equals to given course id.
     *
     * @param int $courseid
     * @return null
     */
    public static function delete_by_course($courseid) {
        global $DB;
        return $DB->delete_records(self::TABLE, array('courseid' => $courseid));
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
     * Validate a courseid.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_courseid($value) {
        global $DB;
        if (!$DB->record_exists('course', ['id' => $value])) {
            return new \lang_string('error_coursenotfound', 'mod_scormremote', $value);
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
