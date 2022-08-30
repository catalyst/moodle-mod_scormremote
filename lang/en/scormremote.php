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
 * Plugin strings are defined here.
 *
 * @package     mod_scormremote
 * @category    string
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']       = 'SCORM Remote';
$string['modulename']       = 'SCORM Remote';
$string['modulenameplural'] = 'SCORM Remote\'s';
$string['pluginadministration'] = 'SCORM Remote module administration';
$string['clientconfig'] = 'Client configurations';
$string['client'] = 'Client';
$string['domain'] = 'Domain';
$string['domain_help'] = 'A domain name is the text that a user types into a browser window to reach a particular website. For instance, the domain name for Google is \'google.com\'.';
$string['manage_clientconfig'] = 'Add new client config';
$string['manage_clientconfigdeletemessage'] = 'Are you absolutely sure you want to delete this client configuration. The seats used by this client will lose access.';
$string['manage_clientconfigdeletesuccess'] = 'Client configuration deleted successfully.';
$string['manage_clientconfigcreateheader'] = 'Add new client configuration';
$string['manage_clientconfigupdateheader'] = 'Update client configuration';
$string['manage_clients'] = 'Manage remote clients';
$string['manage_clientupdateheader'] = 'Update remote client: {$a}';
$string['manage_clientdeletesuccess'] = 'Remote client deleted successfully.';
$string['manage_clientcreateheader'] = 'Add new remote client';
$string['manage_clientadd'] = 'Add a new client';
$string['manage_clientdeletemessage'] = 'Are you absolutely sure you want to completely delete this client and all the data it contains?';
$string['manage_clientname'] = 'Client name';
$string['manage_clientdomain'] = 'Client domain';
$string['seats'] = 'Seats';
$string['seatsinuse'] = 'Seats in use';
$string['wrappedpackagefile'] = 'Wrapped package file';
$string['error_clientconfignan'] = 'The given value for maxseatcount is not a integer.';
$string['error_clientconfignotfound'] = 'The given client config id was not found (id: {$a->id}).';
$string['error_clientconfigclientnotfound'] = 'Couldn\'t find client linked (id: {$a}).';
$string['error_clientconfigscormremotenotfound'] = 'Couldn\'t find scormremote instance (id: {$a}).';
$string['error_clientconfigmaxseatcounttolow'] = 'Value must be greater than or equal to 0.';
$string['error_clientnotfound'] = 'The given client id was not found (id: {$a->id}).';
$string['error_clientnamelength'] = 'Client name must be between 2 and 100 characters, given name contains {$a}.';
$string['error_clientnamenotvalid'] = 'The given value for client name isn\'t valid (name: {$a->name})';
$string['error_clientdomainnotvalid'] = 'The given value "{$a}" is not a valid domain (example: google.com).';
$string['error_clientdomainnotunique'] = 'A client already exists with domain "{$a}". Must be unique.';
$string['error_imsmanifestmissing'] = 'The imsmanifest.xml is missing from the filesystem. Reupload the package might help.';
$string['scormremote:manageclient'] = 'Manage SCORM remote clients';
$string['scormremote:deleteclient'] = 'Delete SCORM remote clients';
$string['scormremote:viewclient'] = 'View SCORM remote clients';
$string['scormremote:manageclientconfig'] = 'Manage configurations for clients in SCORM remote module';
$string['scormremote:deleteclientconfig'] = 'Delete configurations for clients in SCORM remote module';
$string['scormremote:viewclientconfig'] = 'View configurations for clients in SCORM remote module';
$string['scormremote:downloadwrapper'] = 'Download a wrapper package';