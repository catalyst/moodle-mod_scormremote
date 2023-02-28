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
 * Privacy Subsystem implementation for mod_scormremote.
 *
 * @package     mod_scormremote
 * @author      Glenn Poder <glennpoder@catalyst-au.net>
 * @copyright   2023 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scormremote\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy Subsystem implementation for scorm remote.
 *
 */
class provider implements
    // This plugin stores user data.
    \core_privacy\local\metadata\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,

    // This plugin may provide access to and deletion of user data.
    \core_privacy\local\request\plugin\provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {scormremote_course_tiers} sct
                  JOIN {context} ctx ON ctx.instanceid = sct.courseid AND ctx.contextlevel = :contextcourse
                  JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :userid";
        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // No tables are to be exported.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // No tables need to be deleted.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // No tables need to be deleted.
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        if (empty($userlist)) {
            return;
        }

        $context = $userlist->get_context();

        $params = [
            'contextid' => $context->id
        ];

        // Include users that have a role assigned to them.
        $sql = "SELECT userid
                  FROM {role_assignments}
                 WHERE contextid = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // No tables need to be deleted.
    }

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('scormremote', [
        'course' => 'privacy:metadata:mod_scormremote:course',
        'name' => 'privacy:metadata:mod_scormremote:name',
        'reference' => 'privacy:metadata:mod_scormremote:reference',
        'sha1hash' => 'privacy:metadata:mod_scormremote:sha1hash',
        'intro' => 'privacy:metadata:mod_scormremote:intro',
        'introformat' => 'privacy:metadata:mod_scormremote:introformat',
        ], 'privacy:metadata:mod_scormremote:scormremote');

        $collection->add_database_table('scormremote_clients', [
            'name' => 'privacy:metadata:mod_scormremote:name',
            'primarydomain' => 'privacy:metadata:mod_scormremote:primarydomain',
        ], 'privacy:metadata:mod_scormremote:scormremote_clients');

        $collection->add_database_table('scormremote_client_domains', [
            'clientid' => 'privacy:metadata:mod_scormremote:clientid',
            'domain' => 'privacy:metadata:mod_scormremote:domain',
        ], 'privacy:metadata:mod_scormremote:scormremote_client_domains');

        $collection->add_database_table('scormremote_client_domains', [
            'name' => 'privacy:metadata:mod_scormremote:name',
            'seats' => 'privacy:metadata:mod_scormremote:seats',
        ], 'privacy:metadata:mod_scormremote:scormremote_client_domains');

        return $collection;
    }
}
