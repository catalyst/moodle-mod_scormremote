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

defined('MOODLE_INTERNAL') || die();

/**
 * Class for scormremote client configurations. This holds the connection between client and course modules. Each configuration
 *
 * @package     mod_scormremote
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_config {
    const TABLENAME = 'scormremote_client_configs';

    /** The client id to which this configuration is linked. */
    public int $clientid;
    /** The scormremote module instance id linked to this config. */
    public int $scormremoteid;
    /** The number of seats open for allocation for this config. */
    public int $maxseatcount;

    /**
     * The constructor of this class. This is private since we want to force use of create() or read().
     *
     * @param integer $clientid
     * @param integer $scormremoteid
     * @param integer $maxseatcount
     */
    private function __construct(int $clientid, int $scormremoteid, int $maxseatcount = 0) {
        $this->scormremoteid = $scormremoteid;
        $this->clientid = $clientid;
        $this->maxseatcount = $maxseatcount;
    }

    /**
     * Create a instance of this class.
     *
     * @param integer $clientid The client id to which this is linked.
     * @param integer $scormremoteid The scormremoteid to which this is linked.
     * @param integer $maxseatcount The number maximum number of seats allowed.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @throws \moodle_exception When variable maxseatcount is not integer.
     * @return client_config
     */
    public static function create(int $clientid, int $scormremoteid, int $maxseatcount = 0) : self {
        global $DB;

        // Validate client and scorm.
        $DB->get_record(client::TABLENAME, ['id' => $clientid], 'id', MUST_EXIST);
        $DB->get_record('scormremote', ['id' => $scormremoteid], 'id', MUST_EXIST);

        if (!filter_var($maxseatcount, FILTER_VALIDATE_INT)) {
            throw new \moodle_exception('error_clientconfignan', 'mod_scormremote');
        }

        $config = new self($clientid, $scormremoteid, $maxseatcount);

        $DB->insert_record(self::TABLENAME, $config);

        return $config;
    }

    /**
     * Find the client config record from the database based upon it's client config id.
     *
     * @param ins $clientconfigid
     * @throws \moodle_exception When the config cannot be found.
     * @return client_config
     */
    public static function read(int $clientconfigid) : self {
        global $DB;

        $record = $DB->get_record(self::TABLENAME, ['id' => $clientconfigid]);
        if (!$record) {
            $a = new \stdClass();
            $a->id = $clientconfigid;
            throw new \moodle_exception('error_clientconfignotfound', 'mod_scormremote', '', $a);
        }

        $config = new self((int)$record->clientid, (int)$record->scormremoteid, (int)$record->maxseatcount);
        $config->id = $clientconfigid;

        return $config;
    }

    /**
     * Delete this client config. use delete_instance() for a static delete.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return bool
     */
    public function delete() {
        return self::delete_instance($this->id);
    }

    /**
     * Update the a existing record in the database.
     *
     * This is private since developers should use other methods to update properies.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return client_config
     */
    private function update() {
        global $DB;
        $DB->update_record(self::TABLENAME, $this);
        return $this;
    }


    /**
     * Delete a client config by id.
     *
     * @param int $clientconfigid The id you wish to delete.
     * @throws \moodle_exception When validation for id fails.
     * @throws \dml_exception A DML specific exception is thrown for any errors.
     * @return bool
     */
    public static function delete_instance(int $clientconfigid) {
        global $DB;
        if (!isset($clientconfigid) || $clientconfigid <= 0 ) {
            $a = new \stdClass();
            $a->id = $clientconfigid;
            throw new \moodle_exception('error_clientconfignotfound', 'mod_scormremote', '', $a);
        }
        return $DB->delete_records(self::TABLENAME, ['id' => $clientconfigid]);
    }

    /**
     * Update the number of seats.
     *
     * @throws \moodle_exception When validation for name or domain fails.
     * @return bool
     */
    public function set_maxseatcount(int $count) {
        $this->maxseatcount = $count;
        $this->update();
        return true;
    }
}