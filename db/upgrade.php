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

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_scormremote_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022090100) {
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
        upgrade_mod_savepoint(true, 2022090100, 'scormremote');
    }

    if ($oldversion < 2022090101) {

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
        upgrade_mod_savepoint(true, 2022090101, 'scormremote');
    }

    if ($oldversion < 2022090102) {
        // Define key fk_scormremote (foreign) to be dropped form scormremote_client_configs.
        $table = new xmldb_table('scormremote_client_configs');
        $key1 = new xmldb_key('fk_scormremote', XMLDB_KEY_FOREIGN, ['scormremoteid'], 'scormremote', ['id']);
        $key2 = new xmldb_key('fk_client', XMLDB_KEY_FOREIGN, ['clientid'], 'scormremote_client_configs', ['id']);

        // Conditionally launch drop table for scormremote_client_configs.
        if ($dbman->table_exists($table)) {
            $dbman->drop_key($table, $key1);
            $dbman->drop_key($table, $key2);
            $dbman->drop_table($table);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022090102, 'scormremote');
    }

    if ($oldversion < 2022090103) {

        // Define table scormremote_tiers to be created.
        $table = new xmldb_table('scormremote_tiers');

        // Adding fields to table scormremote_tiers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('seats', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scormremote_tiers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for scormremote_tiers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022090103, 'scormremote');
    }

    if ($oldversion < 2022090200) {

        // Define table scormremote_subscriptions to be created.
        $table = new xmldb_table('scormremote_subscriptions');

        // Adding fields to table scormremote_subscriptions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tierid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scormremote_subscriptions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('foreign_subscription_to_clientid', XMLDB_KEY_FOREIGN, ['clientid'], 'scormremote_clients', ['id']);
        $table->add_key('foreign_subscription_to_tierid', XMLDB_KEY_FOREIGN, ['tierid'], 'scormremote_tiers', ['id']);
        $table->add_key('unique_subscription_client_to_tier', XMLDB_KEY_UNIQUE, ['clientid', 'tierid']);

        // Conditionally launch create table for scormremote_subscriptions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022090200, 'scormremote');
    }

    if ($oldversion < 2022090201) {
        // Define table scormremote_course_tiers to be created.
        $table = new xmldb_table('scormremote_course_tiers');

        // Adding fields to table scormremote_course_tiers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tierid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scormremote_course_tiers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('foreign_course_tiers_to_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('foreign_course_tiers_to_tierid', XMLDB_KEY_FOREIGN, ['tierid'], 'scormremote_tiers', ['id']);
        $table->add_key('unique_course_tiers_course_to_tier', XMLDB_KEY_UNIQUE, ['courseid', 'tierid']);

        // Conditionally launch create table for scormremote_course_tiers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2022090201, 'scormremote');
    }

    if ($oldversion < 2023032900) {

        $table = new xmldb_table('scormremote_clients');

        // Define field primarydomain to be added to scormremote_clients.
        $field = new xmldb_field('primarydomain', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Conditionally launch add field primarydomain.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index primarydomain to be added to scormremote_clients.
        $index = new xmldb_index('primarydomain', XMLDB_INDEX_NOTUNIQUE, ['primarydomain']);

        // Conditionally launch add index clientid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Update the primary domain field with the first domain in the
        $sql = "UPDATE {scormremote_clients}
                   SET primarydomain = (SELECT scd.domain
                                          FROM {scormremote_client_domains} scd
                                         WHERE scd.id IN (SELECT MIN(scd1.id) firstdomainid
                                                            FROM {scormremote_client_domains} scd1
                                                        GROUP BY scd1.clientid)
                                         AND scd.clientid = {scormremote_clients}.id)";

        $DB->execute($sql);

        // Remove duplicate domains from the scormremote_client_domains table
        $sql = "DELETE
                  FROM {scormremote_client_domains}
                 WHERE {scormremote_client_domains}.domain IN (SELECT sc.primarydomain
                                        FROM {scormremote_clients} sc
                                       WHERE sc.id = {scormremote_client_domains}.clientid)";

        $DB->execute($sql);

        $table = new xmldb_table('scormremote_client_domains');
        $index = new xmldb_index('unique_client_domain', XMLDB_INDEX_UNIQUE, ['domain']);

        $dbman->drop_index($table, $index);

        $index = new xmldb_index('client_domain', XMLDB_INDEX_NOTUNIQUE, ['domain']);
        $dbman->add_index($table, $index);

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2023032900, 'scormremote');
    }

    if ($oldversion < 2024030701) {

        $table = new xmldb_table('scormremote_clients');

        // Define field expiry to be added to scormremote_clients.
        $field = new xmldb_field('expiry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'primarydomain');

        // Conditionally launch add field expiry.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Scormremote savepoint reached.
        upgrade_mod_savepoint(true, 2024030701, 'scormremote');
    }

        return true;
}
