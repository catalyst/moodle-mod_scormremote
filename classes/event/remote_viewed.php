<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The mod_scormremote tracks when invalid clients view package.
 *
 * @package    mod_scormremote
 * @copyright  2024 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scormremote\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The SCORM remote package viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event properties.
 *
 *      - string origin: The URL attempting to view SCORM remotely.
 *      - string fullname: The full name of the user attempting to view.
 * }
 *
 * @package     mod_scormremote
 * @author      Djarran Cotleanu <djarrancotleanu@catalyst-au.net>
 * @copyright   2024 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remote_viewed extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::USER_OTHER;
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description = $this->other['description'];

        return $description;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_remoteviewed_name', 'mod_scormremote');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = array(
            'id' => $this->contextinstanceid,
        );
        return new \moodle_url('mod/scormremote/view.php', $params);
    }
}
