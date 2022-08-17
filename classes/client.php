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
            'domain' => array(
                'type' => PARAM_RAW,
                'description' => 'The domain associated with the client.',
            ),
        );
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

    /**
     * Validate a client domain.
     *
     * @param string $value
     * @return true|\lang_string
     */
    protected function validate_domain(string $value) {
        if (
            !(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $value) // Uses valid characters.
            && preg_match("/^.{1,253}$/", $value)                    // Is restricted to a maximum length of 253.
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $value)) // Lengths of each label is < 64.
        ) {
            return new \lang_string('error_clientdomainnotvalid', 'mod_scormremote');
        }

        return true;
    }
}