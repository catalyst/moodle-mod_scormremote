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

/**
 * Tests against the client class.
 *
 * @package     mod_scormremote
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scormremote_client_testcase extends \advanced_testcase {
    /**
     * Test function for testing the creation of a new client, it takes parameters passed from the specified data provider.
     *
     * @dataProvider create_new_client_provider
     * @param string $clientname
     * @param string $clientdomain
     *
     * @return void
     */
    public function test_create_new_client(string $clientname, string $clientdomain) {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $client = \mod_scormremote\client::create($clientname, $clientdomain);

    }

    public function create_new_client_provider(): array {
        return [
            'successful test' => [
                'clientname'   => 'Catalyst IT Australia Pty. Ltd.',
                'clientdomain' => 'https://catalyst-au.net',
            ],
        ];
    }
}
