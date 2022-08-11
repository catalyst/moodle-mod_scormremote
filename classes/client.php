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

use stdClass;

use function Aws\filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for scormremote client.
 *
 * @package     mod_scormremote
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    const TABLENAME = 'mod_scormremote_clients';

    /** The ID of the client as set in the database. */
    public int $id;
    /** The name of the client. */
    public string $name;
    /** The domain of the client. */
    public string $domain;


    /**
     * Private since we want the user to use either create() or read().
     *
     * @param string $name
     * @param string $domain
     */
    private function __construct(string $name, string $domain) {
        $this->name = $name;
        $this->domain = $domain;
    }

    /**
     * Create a new client record in the database.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @return client
     */
    public static function create(string $clientname, string $clientdomain) {
        global $DB;

        if (!self::validate_name($clientname)) {
            $a = new \stdClass();
            $a->name = $clientname;
            throw new \moodle_exception('error_clientnamenotvalid', 'mod_scormremote', '', $a);
        }

        if (!self::validate_domain($clientdomain)) {
            $a = new \stdClass();
            $a->domain = $clientdomain;
            throw new \moodle_exception('error_clientdomainnotvalid', 'mod_scormremote', '', $a);
        }

        $client = new self($clientname, $clientdomain);
        $client->id = $DB->insert_record(self::TABLENAME, $client, true);

        return $client;
    }

    /**
     * Find client records from database based upon clientid.
     *
     * @param int $clientid The database id from the client.
     * @throws \moodle_exception When client not found.
     * @return client
     */
    public static function read(int $clientid) : client {
        global $DB;

        $record = $DB->get_record(self::TABLENAME, array('id' => $clientid));
        if (!$record) {
            $a = new \stdClass();
            $a->id = $clientid;
            throw new \moodle_exception('error_clientnotfound', 'mod_scormremote', '', $a);
        }

        $client = new self($record->name, $record->domain);
        $client->id = $record->id;

        return $client;
    }

    /**
     * Update the a existing record in the database.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return client
     */
    public function update() {
        global $DB;

        if (!self::validate_name($this->name)) {
            throw new \moodle_exception('error_clientnamenotvalid', 'mod_scormremote', '', $this);
        }
        if (!self::validate_domain($this->domain)) {
            throw new \moodle_exception('error_clientdomainnotvalid', 'mod_scormremote', '', $this);
        }

        $DB->update_record(self::TABLENAME, $this);
        return $this;
    }

    /**
     * Delete this client. use delete_instance() for a static delete.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return bool
     */
    public function delete() {
        return self::delete_instance($this->id);
    }

    /**
     * Delete a client by id.
     *
     * @param int $clientid The id you wish to delete.
     * @throws \moodle_exception When validation for name or domain fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return bool
     */
    public static function delete_instance(int $clientid) {
        global $DB;
        return $DB->delete_records(self::TABLENAME, ['id' => $clientid]);
    }

    /**
     * Validate a domain.
     *
     * A client domain Validates domain names against RFC 1034, RFC 1035, RFC 952, RFC 1123, RFC 2732, RFC 2181, and RFC 1123.
     * Aditionally the field must remain under 255 characters.
     *
     * @param string $domain
     * @return boolean
     */
    public static function validate_domain(string $domain) : bool {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, [FILTER_FLAG_HOSTNAME])) {
            return false;
        }
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }
        if (strlen($domain) > 255) {
            return false;
        }

        return true;
    }

    /**
     * Validate a client name.
     *
     * A client name must follow these conditions:
     *  - must have under 100 characters in length; and
     *  - must have more than 2 characters (3 min) in lenght; and
     *  - must have no preceding or trailing spaces; and
     *
     * @param string $name
     * @return boolean
     */
    public static function validate_name(string $name) : bool {
        // Must have under 100 characters in length.
        if (strlen($name) > 100) {
            return false;
        }

        // Must have more than 2 characters (3 min) in lenght.
        if (strlen($name) <= 2) {
            return false;
        }

        // Must have no preceding or trailing spaces.
        if (substr($name, 0, 1) == ' ' || substr($name, -1) == ' ') {
            return false;
        }

        return true;
    }
}