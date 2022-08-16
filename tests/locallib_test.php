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

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests against the packagefile class.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scormremote_packagefile_testcase extends \advanced_testcase {
    /**
     * Test the method which expands the html document with the required strings.
     */
    public function test_extend_html_document() {
        $html = "<html><body></body></html>";
        $return = \mod_scormremote\packagefile::add_scormagain_html($html);
        $this->assertNotEquals(false, $html);
        $this->assertNotEquals(null, $html);
        $this->assertNotEquals($html, $return);

        // Multiple closing body.
        $html = "<html><body></body></body></html>";
        $return = \mod_scormremote\packagefile::add_scormagain_html($html);
        $this->assertFalse($return);

        // No closing body.
        $html = "<html><body></html>";
        $return = \mod_scormremote\packagefile::add_scormagain_html($html);
        $this->assertFalse($return);
    }
}
