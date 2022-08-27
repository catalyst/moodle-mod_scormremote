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

defined('MOODLE_INTERNAL') || die();

/**
 * Class contains static utilitarion methods.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Return the imsmanifest as string uploaded to given scormremote instance.
     *
     * @param object $scormremote instance
     * @return string
     */
    public static function get_scormremote_imsmanifest(&$scormremote) {
        // Get the cm for context.
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        $context = \context_module::instance($scormremote->coursemodule);

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_scormremote', 'content', 0, '/', 'imsmanifest.xml');

        // This should not happen.
        if (!$file) {
            throw new \moodle_exception('error_imsmanifestmissing', 'mod_scormremote');
        }

        return $file->get_content();
    }

    /**
     * Function to get course module context given a scormremote context. It will set $scoremote->coursemodule to the course module
     * id.
     *
     * @param object $scormremote instance
     * @return \context_module
     */
    public static function get_context(&$scormremote) {
        if (!isset($scormremote->coursemodule)) {
            $cm = get_coursemodule_from_instance('scormremote', $scormremote->id);
            $scormremote->coursemodule = $cm->id;
            unset($cm);
        }
        return \context_module::instance($scormremote->coursemodule);
    }
}