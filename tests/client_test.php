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
     * @dataProvider crud_client_provider
     * @param string $clientname
     * @param string $clientdomain
     * @param bool   $ok
     *
     * @return void
     */
    public function test_crud_client(string $clientname, string $clientdomain, bool $ok) {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();


        if (!$ok) {
            // Validate.
            $validation = \mod_scormremote\client::validate_name($clientname);
            $validation = $validation && \mod_scormremote\client::validate_domain($clientdomain);
            $this->assertFalse($validation);
            return;
        }

        // Validate.
        $this->assertTrue(\mod_scormremote\client::validate_name($clientname));
        $this->assertTrue(\mod_scormremote\client::validate_domain($clientdomain));

        // Create.
        $client = \mod_scormremote\client::create($clientname, $clientdomain);
        $this->assertNotFalse($client);
        $this->assertEquals($clientname, $client->name);
        $this->assertEquals($clientdomain, $client->domain);

        $clientrecords = $DB->get_records(\mod_scormremote\client::TABLENAME);
        $this->assertCount(1, $clientrecords);
        $key = array_key_first($clientrecords);
        $this->assertEquals($key, $clientrecords[$key]->id);
        $this->assertEquals($clientname, $clientrecords[$key]->name);
        $this->assertEquals($clientdomain, $clientrecords[$key]->domain);

        // Read.
        $readclient = \mod_scormremote\client::read($key);
        $this->assertEquals($key, $readclient->id);
        $this->assertEquals($clientname, $readclient->name);
        $this->assertEquals($clientdomain, $readclient->domain);

        // Update.
        $newclientname = "A new start";
        $readclient->name = $newclientname;
        $readclient->update();
        $key = $readclient->id;
        $readclient = \mod_scormremote\client::read($key);
        $this->assertEquals($key, $readclient->id);
        $this->assertEquals($newclientname, $readclient->name);
        $this->assertEquals($clientdomain, $readclient->domain);

        // Delete.
        $this->assertTrue($readclient->delete());
    }

    public function crud_client_provider(): array {
        return [
            'successful test' => [
                'clientname'   => 'Catalyst IT Australia Pty. Ltd.',
                'clientdomain' => 'https://catalyst-au.net',
                'ok'           => true
            ],
            '.net-less domain test' => [
                'clientname'   => 'Catalyst IT Australia Pty. Ltd.',
                'clientdomain' => 'https://catalyst-au',
                'ok'           => true
            ],
            'incorrrect domain test' => [
                'clientname'   => 'Catalyst IT Australia Pty. Ltd.',
                'clientdomain' => 'c@talyst-au.net',
                'ok'           => false
            ],
        ];
    }
}
