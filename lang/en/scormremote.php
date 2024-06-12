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
$string['client'] = 'Client';
$string['domain'] = 'Domain';
$string['domain_help'] = 'A domain name is the text that a user types into a browser window to reach a particular website. For instance, the domain name for Google is \'google.com\'.';
$string['embedclientid'] = 'Embed client id';
$string['chooseclient'] = 'Embed Client id';
$string['searchclient'] = 'Search client';
$string['optionalsettings'] = 'optional settings';
$string['chooseclientdesc'] = 'Associates the wrapper with a particular client';
$string['manage_adddomain'] = 'Add domain';
$string['manage_additionalclientdomain'] = 'Allowed client domains';
$string['manage_alloweddomains'] = 'Extra allowed domains';
$string['manage_domains_desc'] = 'Users coming from these domains are allowed but will appear as coming from the primary client domain.';
$string['manage_clients'] = 'Manage clients';
$string['manage_clientdetails'] = 'Client details';
$string['manage_clientupdateheader'] = 'Update client: {$a}';
$string['manage_clientdeletesuccess'] = 'Client deleted successfully.';
$string['manage_clientcreateheader'] = 'Add new client';
$string['manage_clientadd'] = 'Add a new client';
$string['manage_clientdeletemessage'] = 'Are you absolutely sure you want to completely delete this client and all the data it contains?';
$string['manage_clientname'] = 'Client name';
$string['manage_clientdomain'] = 'Client domain';
$string['manage_primaryclientdomain'] = 'Primary client domain';
$string['manage_subscriptions'] = 'Course subscriptions';
$string['manage_tiers'] = 'Manage tiers';
$string['manage_tiersdescription'] = 'Tiers are a way to limit the amount of participants per client. That limit is called seats. Client can subscribe to a tier, which allow for them to have acces to all modules in the configured courses. Below table shows all configured tiers ordered by seats.';
$string['manage_tieradd'] = 'Add new tier';
$string['manage_tiercreateheader'] = 'Add new tier';
$string['manage_tierdeletemessage'] = 'Are you absolutely sure you want to completely delete this tier and all the data it contains?';
$string['manage_tierdeletesuccess'] = 'Tier deleted successfully.';
$string['manage_tierdescription'] = 'Description';
$string['manage_tiername'] = 'Name';
$string['manage_tierseats'] = 'Seats';
$string['manage_tierscmexplaination'] = '* S / C / M stand for subscribers, course and modules.';
$string['filename'] = 'Filename';
$string['filenameother'] = 'Other filename';
$string['filenameother_help'] = 'Will only be used when "Other" is selected above. Must be a valid filename. If the filename doesn\'t end with ".zip" the system adds it for you.';
$string['filenameother_error'] = 'Given filename is not allowed. Use only the following characters 0-9, a-z, A-Z, spaces, ., _ or -.';
$string['seats'] = 'Seats';
$string['seatsinuse'] = 'Seats in use';
$string['subs'] = 'Subscriptions';
$string['subscribers'] = 'Subscribers';
$string['wrapper'] = 'Wrapper';
$string['wrappedpackagefile'] = 'Wrapped package file';
$string['error_clientnotfound'] = 'The given client id was not found (id: {$a->id}).';
$string['error_clientnamelength'] = 'Client name must be between 2 and 100 characters, given name contains {$a}.';
$string['error_clientnamenotvalid'] = 'The given value for client name isn\'t valid (name: {$a->name})';
$string['error_clientdomainnotvalid'] = 'The given value "{$a}" is not a valid domain (example: google.com).';
$string['error_clientdomainnotunique'] = 'A client already exists with domain "{$a}". Must be unique.';
$string['error_coursenotfound'] = 'The given course id was not found (id: {$a->id}).';
$string['error_coursesnotunique'] = 'Multiple tiers can only be selected when each of these has a unique set of courses.';
$string['error_tiernotfound'] = 'The given tier id was not found (id: {$a->id}).';
$string['error_tiernamelength'] = 'Tier name must be between 1 and 100 characters, given name contains {$a}.';
$string['error_tierseatnumber'] = 'Tier seats must be greater than or equal to 0.';
$string['error_imsmanifestmissing'] = 'The imsmanifest.xml is missing from the filesystem. Reupload the package might help.';
$string['event_error_name'] = 'SCORM Remote error';
$string['event_seatallocated_name'] = 'SCORM Remote seat allocated';
$string['event_remoteviewed_name'] = 'SCORM Remote viewed';
$string['event_nosubscription'] = 'The user \'{$a->fullname}\' was denied access to a SCORM package for the course with id \'{$a->courseid}\' as the client \'{$a->clientname}\' does not have a subscription that includes this package.';
$string['event_seatlimitreached'] = 'The user \'{$a->fullname}\' was denied access to a SCORM package for the course with id \'{$a->courseid}\' as the client \'{$a->clientname}\' has reached its seat limit ({$a->seatlimit}).';
$string['event_scormviewed'] =  'The user \'{$a->fullname}\' viewed a SCORM package for the course with id \'{$a->courseid}\' from client \'{$a->clientname}\'.';
$string['event_seatallocated'] =  'A new seat ({$a->seatcount}/{$a->seatlimit}) was allocated for client \'{$a->clientname}\' for user \'{$a->fullname}\'.';
$string['event_unknownorigin'] = 'The user \'{$a->fullname}\' was denied access to a SCORM package for the course with id \'{$a->courseid}\' as the request originated from an unknown client \'{$a->origin}\'.';
$string['scormremote:addinstance'] = 'Add SCORM remote instance';
$string['scormremote:manageclient'] = 'Manage SCORM remote clients';
$string['scormremote:deleteclient'] = 'Delete SCORM remote clients';
$string['scormremote:viewclient'] = 'View SCORM clients';
$string['scormremote:managetier'] = 'Manage SCORM remote tiers';
$string['scormremote:deletetier'] = 'Delete SCORM remote tiers';
$string['scormremote:viewtier'] = 'View SCORM remote tiers';
$string['scormremote:downloadwrapper'] = 'Download a wrapper package';
$string['errorpage_badrequesttitle'] = 'Bad request';
$string['errorpage_badrequestmessage'] = 'The server cannot handle this request, you must make modifications to this request in order to proceed.';
$string['errorpage_unauthorizedtitle'] = 'Unauthorized';
$string['errorpage_unauthorizedmessage'] = 'This request is not authorized to continue. Contact your teacher to resolve this problem.';
$string['errorpage_subrequiredtitle'] = 'Subscription required';
$string['errorpage_subrequiredmessage'] = 'This content is only available for subscribed users. Contact your teacher to resolve this problem.';
$string['errorpage_sublimittitle'] = 'Subscription limit reached';
$string['errorpage_sublimitmessage'] = 'Subscription limit has been reached. Contact your teacher to resolve this problem.';
$string['privacy:metadata:mod_scormremote:scormremote'] = 'Stores the scormremote activity module instances.';
$string['privacy:metadata:mod_scormremote:course'] = 'ID of the course this activity is part of.';
$string['privacy:metadata:mod_scormremote:name'] = 'The name of the activity module instance';
$string['privacy:metadata:mod_scormremote:reference'] = 'The filename of the .zip/.xml that was uploaded.';
$string['privacy:metadata:mod_scormremote:sha1hash'] = 'package content or ext path hash';
$string['privacy:metadata:mod_scormremote:intro'] = 'Activity description.';
$string['privacy:metadata:mod_scormremote:introformat'] = 'The format of the intro field.';
$string['privacy:metadata:mod_scormremote:scormremote_clients'] = 'Storage for configured clients, clients at a system level.';
$string['privacy:metadata:mod_scormremote:name'] = 'The name of the client.';
$string['privacy:metadata:mod_scormremote:primarydomain'] = 'The primary domain of the client.';
$string['privacy:metadata:mod_scormremote:scormremote_client_domains'] = 'This is where client domains are stored.';
$string['privacy:metadata:mod_scormremote:clientid'] = 'The client id to which this domain is linked.';
$string['privacy:metadata:mod_scormremote:domain'] = 'The domain.';
$string['privacy:metadata:mod_scormremote:scormremote_tiers'] = 'Holds information about seats and is linked to subscriptions and courses.';
$string['privacy:metadata:mod_scormremote:name'] = 'The name of the tier';
$string['privacy:metadata:mod_scormremote:seats'] = 'The maximum allowable seats.';
