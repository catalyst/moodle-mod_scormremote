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
 * Upgrade script for the scorm remote module.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_scormremote_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022083001) {
        // Define table scormremote_client_domains to be created.
        $table = new xmldb_table('scormremote_client_domains');

        // Adding fields to table scormremote_client_domains.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('domain', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scormremote_client_domains.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table scormremote_client_domains.
        $table->add_index('unique_client_domain', XMLDB_INDEX_UNIQUE, ['domain']);

        // Conditionally launch create table for scormremote_client_domains.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Move the scormremote_clients.domain value into this new table.
        $sql = "INSERT INTO {scormremote_client_domains} (clientid, domain, usermodified, timecreated, timemodified)
                SELECT id as clientid, domain, usermodified, timecreated, timemodified
                  FROM {scormremote_clients} client
                 WHERE NOT EXISTS (
                     SELECT 1
                       FROM {scormremote_client_domains}
                      WHERE domain = client.domain)";
        $DB->execute($sql);

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022083001, 'scormremote');
    }

    if ($oldversion < 2022083100) {

        // Define index unique_domain (unique), and field domain to be dropped form scormremote_clients.
        $table = new xmldb_table('scormremote_clients');
        $index = new xmldb_index('unique_domain', XMLDB_INDEX_UNIQUE, ['domain']);
        $field = new xmldb_field('domain');

        // Conditionally launch drop index unique_domain.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Conditionally launch drop field domain.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022083100, 'scormremote');
    }

    return true;
}
