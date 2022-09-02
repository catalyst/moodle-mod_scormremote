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

/**
 * This page is for managing scormremote tiers. It does multiple things:
 *
 *  1. Provide a view for of all configured tiers.
 *  2. When $_GET['editingon']=1 then we're adding a tier.
 *  3. In addition to above also $_GET['id'] is provided we're changing a tier.
 *  4. Able to delete tiers.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_scormremote\client;
use mod_scormremote\tier;
use mod_scormremote\form\tier as tier_form;
use mod_scormremote\subscription;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$BASEURL = '/mod/scormremote/tiers.php';

// Check if we go an ID.
$id       = optional_param('id', null, PARAM_INT);
$editing  = optional_param('editingon', false, PARAM_BOOL);
$deleting = optional_param('deleting', false, PARAM_BOOL);
$delete   = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

// No guest autologin.
require_login(0, false);
$PAGE->set_url(new moodle_url($BASEURL, ['id' => $id, 'editingon' => $editing]));
admin_externalpage_setup('scormremotetiers');
$PAGE->set_title("{$SITE->shortname}: " . get_string('manage_tiers', 'mod_scormremote'));

// Authorize.
$context = context_system::instance();
if ($editing) {
    require_all_capabilities(['mod/scormremote:manageclient', 'mod/scormremote:managetier'], $context);
} else if ($deleting) {
    require_capability('mod/scormremote:deletetier', $context);
} else {
    require_capability('mod/scormremote:viewtier', $context);
}

// Instantiate a tier object if we recieved an ID.
$tier = null;
if (!empty($id)) {
    $tier = new tier($id);
    $PAGE->navbar->add($tier->get('name'));
}

// Handling create or update.
if ($editing) {
    // Create the form instance.
    $customdata = [
        'persistent' => $tier,
        'userid' => $USER->id,
        'clients' => [],
    ];

    // Setup subscribers if editing.
    if ($tier) {
        $clients = client::get_records_by_tierid((int)$tier->get('id'));
        $customdata['clients'] = array_map(function($client) {
            return (int) $client->get('id');
        }, $clients);
    }
    $form = new tier_form(new moodle_url($BASEURL, ['id' => $id, 'editingon' => 1]), $customdata);

    if ($form->is_cancelled()) {
        // Form cancelled.
        redirect(new moodle_url($BASEURL));
    } elseif (($data = $form->get_data())) {
        // Handle form submission.
        $transaction = $DB->start_delegated_transaction();

        try {
            $clients = $data->clients;
            unset($data->clients);

            if (empty($data->id)) {
                // Create a new record.
                $tier = new tier(0, $data);
                $tier->create();
            } else {
                // Update a record.
                // Delete all prior entered entries.
                subscription::delete_by_tier($tier->get('id'));

                $tier->from_record($data);
                $tier->update();
            }

            // Add the subscriptions.
            foreach (array_unique($clients) as $client) {
                $data = (object) array('clientid' => $client, 'tierid' => $tier->get('id'));
                $sub = new subscription(0, $data);
                $sub->create();
            }

            // Only if everything succeeds we commit.
            $transaction->allow_commit();
            \core\notification::success(get_string('changessaved'));
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
            $transaction->rollback($e);
        }

        // We are done, so let's redirect to base.
        redirect(new moodle_url($BASEURL));
    }
}

// Handling delete.
if ($deleting && $tier && $delete === md5($tier->get('name'))) {
    try {
        $tier->delete();
        \core\notification::success(get_string('manage_tierdeletesuccess', 'mod_scormremote'));
    } catch (Exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect(new moodle_url($BASEURL));
}

// Handling read.
if (!$editing && !$deleting) {
    $tiers = tier::get_records([], $sort = 'seats');

    // Create a table, with three colums; name, domain, actions.
    $table = new html_table();
    $table->head = [
        get_string('manage_tiername', 'mod_scormremote'),
        get_string('manage_tierseats', 'mod_scormremote'),
        get_string('manage_tierdescription', 'mod_scormremote'),
        get_string('subscribers', 'mod_scormremote'),
        get_string('actions'),
    ];

    // Action icons.
    $editicon = $OUTPUT->pix_icon('i/settings', get_string('edit'));
    $deleteicon = $OUTPUT->pix_icon('i/delete', get_string('delete'));

    foreach ($tiers as $tier) {
        $editurl = new moodle_url($BASEURL, ['id' => $tier->get('id'), 'editingon' => 1]);
        $editaction = html_writer::link($editurl, $editicon);
        $deleteurl =  new moodle_url($BASEURL, ['id' => $tier->get('id'), 'deleting' => 1]);
        $deleteaction = html_writer::link($deleteurl, $deleteicon);

        $table->data[] = [
            $tier->get('name'),
            $tier->get('seats'),
            $tier->get('description'),
            subscription::count_records(['tierid' => $tier->get('id')]),
            $editaction . $deleteaction,
        ];
    }
}

// Start of output.
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');

if ($editing && $tier == null) {
    // Creating.
    echo $OUTPUT->heading(get_string('manage_tiercreateheader', 'mod_scormremote'), 2);
    $form->display();
} else if ($editing) {
        // Updating.
    echo $OUTPUT->heading(get_string('manage_tiercreateheader', 'mod_scormremote', $tier->get('name')), 2);
    $form->display();
} else if ($deleting && $tier) {
    // Deleting.
    // This is showing a confimation box, no header here.
    $deletemsg = get_string('manage_tierdeletemessage', 'mod_scormremote');
    $message = "{$deletemsg}</br></br>{$tier->get('name')}";

    $confirmurl = new moodle_url($BASEURL, ['id' => $id, 'deleting' => 1, 'delete' => md5($tier->get('name'))]);
    $confirmbtn = new single_button($confirmurl, get_string('delete'), 'post');
    echo $OUTPUT->confirm($message, $confirmbtn, new moodle_url($BASEURL));
} else {
    // Reading,
    echo $OUTPUT->heading(get_string('manage_tiers', 'mod_scormremote'), 2);
    echo html_writer::table($table);
    $createnewurl = new moodle_url($BASEURL, ['editingon' => 1]);
    echo $OUTPUT->single_button($createnewurl, get_string('manage_tieradd', 'mod_scormremote'));
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();