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
 * Tests against the client class.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_scormremote\client
 */
class client_test extends \advanced_testcase {
    /**
     * Testing the validation of the client object.
     *
     * @dataProvider client_validation_provider
     * @param string $clientname
     * @param string $clientdomain
     * @param bool   $ok
     *
     * @covers ::create
     *
     * @return void
     */
    public function test_validation_of_client(string $clientname, string $clientdomain, bool $ok) {
        // Skipping this test.
        // TODO: issue 24.

        $this->markTestSkipped();

        $this->resetAfterTest();
        $data = new stdClass();
        $data->name = $clientname;
        $data->domain = $clientdomain;
        $client = new \mod_scormremote\client(0, $data);
        $this->assertEquals($ok, $client->is_valid());

        if (!$ok) {
            $this->expectException(\core\invalid_persistent_exception::class);
            $client->create();
            return;
        }

        // Create and refetch the client.
        $client->create();
        $client = new \mod_scormremote\client($client->get('id'));
        $this->assertEquals($clientname, $client->get('name'));
        $this->assertEquals($clientdomain, $client->get('domain'));
    }

    /**
     * Data array for client validation.
     *
     * @return array[]
     */
    public function client_validation_provider(): array {
        return [
            'single char domain' => ['clientname' => 'Foo', 'clientdomain' => 'a',                       'ok' => true],
            'single num domain'  => ['clientname' => 'Foo', 'clientdomain' => '0',                       'ok' => true],
            'a.b domain'         => ['clientname' => 'Foo', 'clientdomain' => 'a.b',                     'ok' => true],
            'localhost domain'   => ['clientname' => 'Foo', 'clientdomain' => 'localhost',               'ok' => true],
            'google domain'      => ['clientname' => 'Foo', 'clientdomain' => 'google.com',              'ok' => true],
            'muliple secions'    => ['clientname' => 'Foo', 'clientdomain' => 'news.google.co.uk',       'ok' => true],
            'randomly generated' => ['clientname' => 'Foo', 'clientdomain' => 'xn--fsqu00a.xn--0zwm56d', 'ok' => true],
            'space in middle'    => ['clientname' => 'Foo', 'clientdomain' => 'goo gle.com',             'ok' => false],
            'section len 0'      => ['clientname' => 'Foo', 'clientdomain' => 'google..com',             'ok' => false],
            'trailing space'     => ['clientname' => 'Foo', 'clientdomain' => 'google.com ',             'ok' => false],
            'subdomain ending -' => ['clientname' => 'Foo', 'clientdomain' => 'google-.com',             'ok' => false],
            'starting with .'    => ['clientname' => 'Foo', 'clientdomain' => '.google.com',             'ok' => false],
            'javascript'         => ['clientname' => 'Foo', 'clientdomain' => '<script',                 'ok' => false],
            'javascript 2'       => ['clientname' => 'Foo', 'clientdomain' => 'alert(',                  'ok' => false],
            'domain .'           => ['clientname' => 'Foo', 'clientdomain' => '.',                       'ok' => false],
            'domain ..'          => ['clientname' => 'Foo', 'clientdomain' => '..',                      'ok' => false],
            'space'              => ['clientname' => 'Foo', 'clientdomain' => ' ',                       'ok' => false],
            'dash'               => ['clientname' => 'Foo', 'clientdomain' => '-',                       'ok' => false],
            'empty'              => ['clientname' => 'Foo', 'clientdomain' => '',                        'ok' => false],
            'short client name'  => ['clientname' => 'f',   'clientdomain' => 'google.com',              'ok' => false],
            'empty client name'  => ['clientname' => '',    'clientdomain' => 'google.com',              'ok' => false],
            'long client name'   => [
                'clientname'   =>
                    'the length is 101 aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'clientdomain' => 'google.com',
                'ok'           => false
            ],
        ];
    }
}
