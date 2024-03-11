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
 * Display error page which has risen usually because of restricted access.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No login check is expected here because this is accessed from external LMS
// and this page only displays error information.
// @codingStandardsIgnoreLine
require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$errorstring = optional_param('error', null, PARAM_RAW_TRIMMED);
$PAGE->set_url('/mod/scormremote/error.php', array('error' => $errorstring));
$PAGE->set_context(context_system::instance());

switch ($errorstring) {
    case 'unauthorized':
        $templatedata = [
            'errorcode'    => 401,
            'errortitle'   => get_string('errorpage_unauthorizedtitle', 'mod_scormremote'),
            'errormessage' => get_string('errorpage_unauthorizedmessage', 'mod_scormremote'),
        ];
        $PAGE->set_title('401 Unauthorized');
        break;
    case 'subrequired':
        $templatedata = [
            'errorcode'    => 402,
            'errortitle'   => get_string('errorpage_subrequiredtitle', 'mod_scormremote'),
            'errormessage' => get_string('errorpage_subrequiredmessage', 'mod_scormremote'),
        ];
        $PAGE->set_title('402 Payment Required');
        break;
    case 'sublimitreached':
        $templatedata = [
            'errorcode'    => 402,
            'errortitle'   => get_string('errorpage_sublimittitle', 'mod_scormremote'),
            'errormessage' => get_string('errorpage_sublimitmessage', 'mod_scormremote'),
        ];
        $PAGE->set_title('402 Payment Required');
        break;
    case 'expired':
        $templatedata = [
            'errorcode'    => 402,
            'errortitle'   => get_string('errorpage_expiredtitle', 'mod_scormremote'),
            'errormessage' => get_string('errorpage_expiredmessage', 'mod_scormremote'),
        ];
        $PAGE->set_title('402 Payment Required');
        break;
    default:
        $templatedata = [
            'errorcode'    => 400,
            'errortitle'   => get_string('errorpage_badrequesttitle', 'mod_scormremote'),
            'errormessage' => get_string('errorpage_badrequestmessage', 'mod_scormremote'),
        ];
        $PAGE->set_title('400 Bad Request');
        break;
}

exit($OUTPUT->render_from_template('mod_scormremote/errorpage', $templatedata));
