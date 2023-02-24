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
 * This page is for managing scormremote clients. It does multiple things:
 *
 *  1. Provide a view for of all configured clients.
 *  2. When $_GET['editingon']=1 then we're adding a client.
 *  3. In addition to above also $_GET['id'] is provided we're changing a client
 *  4. Able to delete clients.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

use \mod_scormremote\client;
use \mod_scormremote\client_domain;
use \mod_scormremote\form\client as client_form;
use mod_scormremote\subscription;
use mod_scormremote\tier;
use \mod_scormremote\utils;

$baseurl = '/mod/scormremote/clients.php';

// Check if we go an ID.
$id       = optional_param('id', null, PARAM_INT);
$editing  = optional_param('editingon', false, PARAM_BOOL);
$deleting = optional_param('deleting', false, PARAM_BOOL);
$delete   = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

// No guest autologin.
require_login(0, false);
$PAGE->set_url(new moodle_url($baseurl, ['id' => $id, 'editingon' => $editing]));
admin_externalpage_setup('scormremoteclients');
$PAGE->set_title("{$SITE->shortname}: " . get_string('manage_clients', 'mod_scormremote'));

// Authorize.
$context = context_system::instance();
if ($editing) {
    require_all_capabilities(['mod/scormremote:manageclient', 'mod/scormremote:managetier'], $context);
} else if ($deleting) {
    require_capability('mod/scormremote:deleteclient', $context);
} else {
    require_capability('mod/scormremote:viewclient', $context);
}

// Instantiate a client object if we received an ID.
$client = null;
if (!empty($id)) {
    $client = new client($id);
    $PAGE->navbar->add($client->get('name'));
}

// Handling create or update.
if ($editing) {
    // Create the form instance. We need to use the current URL and the custom data.
    $customdata = [
        'persistent' => $client,
        'userid'     => $USER->id,
        'domains'    => [],
        'tiers'      => [],
    ];

    // For customdata we need to supple a PHP_EOL seperated string for domains.
    if ($client) {
        $domains = client_domain::get_domain_for_client((int)$client->get('id'));
        $customdata['domains'] = $domains;

        $tiers = tier::get_records_by_clientid((int)$client->get('id'));
        $customdata['tiers'] = array_map(function($tier) {
            return (int) $tier->get('id');
        }, $tiers);
    }

    $form = new client_form(new moodle_url($baseurl, ['id' => $id, 'editingon' => 1]), $customdata);

    if ($form->is_cancelled()) {
        // Form cancelled.
        redirect(new moodle_url($baseurl));
    } else if (($data = $form->get_data())) {
        // Start transaction for creating/updating client, domains and subscriptions.
        $transaction = $DB->start_delegated_transaction();

        try {
            $domains = $data->domains;
            $tiers = $data->tiers;
            // We need to remove domain, tiers and mform settings before creating the client.
            unset($data->domains, $data->tiers, $data->mform_isexpanded_id_clientdetails,
                $data->mform_isexpanded_id_alloweddomains, $data->mform_isexpanded_id_subscriptions);

            if (empty($data->id)) {
                // Create a new record.
                $client = new client(0, $data);
                $client->create();
            } else {
                // Update a record.
                // First delete all prior entered entries.
                client_domain::delete_by_client($client->get('id'));
                subscription::delete_by_client($client->get('id'));

                $client->from_record($data);
                $client->update();
            }

            // Add the unique domains.
            foreach (array_unique($domains) as $domain) {
                $data = (object) array('clientid' => $client->get('id'), 'domain' => $domain);
                $domain = new client_domain(0, $data);
                $domain->create();
            }

            // Add the subscriptions.
            foreach (array_unique($tiers) as $tier) {
                $data = (object) array('clientid' => $client->get('id'), 'tierid' => $tier);
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
        redirect(new moodle_url($baseurl));
    }
}

// Handling delete.
if ($deleting && $id && $client && $delete === md5($client->get('name'))) {
    try {
        $client->delete();
        \core\notification::success(get_string('manage_clientdeletesuccess', 'mod_scormremote'));
    } catch (Exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect(new moodle_url($baseurl));
}

// Handling read. Only do this when !$editing and !$deleting.
if (!$editing && !$deleting) {
    // Not editing? Display the clients table.
    $clients = client::get_records([], $sort = 'name');

    // Create a table, with three colums; name, domain, actions.
    $table = new html_table();
    $table->head = [
        get_string('manage_clientname', 'mod_scormremote'),
        get_string('manage_clientdomain', 'mod_scormremote'),
        get_string('subs', 'mod_scormremote'),
        get_string('actions'),
    ];

    $editicon = $OUTPUT->pix_icon('i/settings', get_string('edit'));
    $deleteicon = $OUTPUT->pix_icon('i/delete', get_string('delete'));

    // Create remote client table rows.
    foreach ($clients as $client) {
        $editurl = new moodle_url($baseurl, ['id' => $client->get('id'), 'editingon' => 1]);
        $editaction = html_writer::link($editurl, $editicon);
        $deleteurl = new moodle_url($baseurl, ['id' => $client->get('id'), 'deleting' => 1]);
        $deleteaction = html_writer::link($deleteurl, $deleteicon);
        $domains = client_domain::get_domain_for_client($client->get('id'));

        if ($client->get('primarydomain')) {
            array_unshift($domains, $client->get('primarydomain'));
        }

        $subs = array();
        foreach (subscription::get_records_by_clientid($client->get('id')) as $sub) {
            $tier = new tier($sub->get('tierid'));
            $subs[] = "{$tier->get('name')} ( {$sub->get_participant_count()} / {$tier->get('seats')} )";
        }


        $table->data[] = [
            $client->get('name'),
            implode("</br>", $domains),
            implode(', ', $subs),
            $editaction . $deleteaction,
        ];
    }
}

// Start of output.
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');

if ($editing && $id == null && $client == null) {
    // Creating.
    echo $OUTPUT->heading(get_string('manage_clientcreateheader', 'mod_scormremote'), 2);
    $form->display();

} else if ($editing && $id && $client) {
    // Updating.
    echo $OUTPUT->heading(get_string('manage_clientupdateheader', 'mod_scormremote', $client->get('name')), 2);
    $form->display();

} else if ($deleting && $id && $client) {
    // Deleting.
    // This is showing a confimation box, no header here.
    $deletemsg = get_string('manage_clientdeletemessage', 'mod_scormremote');
    $message = "{$deletemsg}</br></br>{$client->get('name')}";

    $confirmurl = new moodle_url($baseurl, ['id' => $id, 'deleting' => 1, 'delete' => md5($client->get('name'))]);
    $confirmbtn = new single_button($confirmurl, get_string('delete'), 'post');
    echo $OUTPUT->confirm($message, $confirmbtn, new moodle_url($baseurl));

} else {
    // Reading.
    echo $OUTPUT->heading(get_string('manage_clients', 'mod_scormremote'), 2);

    $createnewurl = new moodle_url($baseurl, ['editingon' => 1]);
    echo html_writer::table($table);
    echo $OUTPUT->single_button($createnewurl, get_string('manage_clientadd', 'mod_scormremote'));

}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
