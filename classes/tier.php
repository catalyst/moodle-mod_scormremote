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
 * Class for loading/storing scormremote tier from the DB.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tier extends \core\persistent {
    /** Database table. */
    const TABLE = 'scormremote_tiers';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'name' => array(
                'type' => PARAM_TEXT,
                'description' => 'The name of this tier.',
            ),
            'seats' => array(
                'type' => PARAM_INT,
                'description' => 'The amount of allocatable seats.'
            ),
            'description' => array(
                'type' => PARAM_TEXT,
                'description' => 'A description for this tier.'
            ),
        );
    }

    /**
     * Validate a tier name.
     *
     * A tier name must follow these conditions:
     *  - must have under 100 characters in length; and
     *  - must have more than 1 characters (3 min) in lenght; and
     *
     * @param string $value
     * @return true|\lang_string
     */
    protected function validate_name(string $value) {
        $len = strlen($value);

        // Must be between 1 and 100 characters in length.
        if ($len <= 1 || $len > 100) {
            return new \lang_string('error_tiernamelength', 'mod_scormremote', $len);
        }

        return true;
    }

    /**
     * Validate a tier seats.
     *
     * A tier seats must follow these conditions:
     *  - must be greater than 0.
     *
     * @param int $value
     * @return true|\lang_string
     */
    protected function validate_seats(int $value) {
        if ($value < 0) {
            return new \lang_string('error_tierseatnumber', 'mod_scormremote');
        }

        return true;
    }
}