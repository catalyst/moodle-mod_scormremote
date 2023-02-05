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
 * The main mod_scormremote configuration form.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/scorm/lib.php');

/**
 * Module instance settings form.
 *
 * @package     mod_scormremote
 * @author      Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scormremote_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Summary.
        $this->standard_intro_elements();

        // Adding the rest of mod_scormremote settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
        $mform->setExpanded('packagehdr', true);

        // New local package upload.
        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = array('.zip', '.xml');
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['subdirs'] = 0;

        $mform->addElement('filemanager', 'packagefile', get_string('package', 'scorm'), null, $filemanageroptions);
        $mform->addHelpButton('packagefile', 'package', 'scorm');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Form preprocessing.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        global $COURSE;

        if (isset($defaultvalues['popup']) && ($defaultvalues['popup'] == 1) && isset($defaultvalues['options'])) {
            if (!empty($defaultvalues['options'])) {
                $options = explode(',', $defaultvalues['options']);
                foreach ($options as $option) {
                    list($element, $value) = explode('=', $option);
                    $element = trim($element);
                    $defaultvalues[$element] = trim($value);
                }
            }
        }

        $scorms = get_all_instances_in_course('scormremote', $COURSE);
        $coursescorm = current($scorms);

        $draftitemid = file_get_submitted_draft_itemid('packagefile');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_scormremote', 'package', 0,
            array('subdirs' => 0, 'maxfiles' => 1));
        $defaultvalues['packagefile'] = $draftitemid;

        if (($COURSE->format == 'singleactivity') && ((count($scorms) == 0) || ($defaultvalues['instance'] == $coursescorm->id))) {
            $defaultvalues['redirect'] = 'yes';
            $defaultvalues['redirecturl'] = '../course/view.php?id='.$defaultvalues['course'];
        } else {
            $defaultvalues['redirect'] = 'no';
            $defaultvalues['redirecturl'] = '../mod/scormremote/view.php?id='.$defaultvalues['coursemodule'];
        }
        if (isset($defaultvalues['instance'])) {
            $defaultvalues['datadir'] = $defaultvalues['instance'];
        }
    }

    /**
     * Form validation.
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $CFG, $USER;

        $errors = parent::validation($data, $files);

        if (empty($data['packagefile'])) {
            $errors['packagefile'] = get_string('required');

        } else {
            $draftitemid = file_get_submitted_draft_itemid('packagefile');

            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_scormremote', 'packagefilecheck', null,
                array('subdirs' => 0, 'maxfiles' => 1));

            // Get file from users draft area.
            $usercontext = context_user::instance($USER->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

            if (count($files) < 1) {
                $errors['packagefile'] = get_string('required');
                return $errors;
            }
            $file = reset($files);

            if (strtolower($file->get_filename()) == 'imsmanifest.xml') {
                if (!$file->is_external_file()) {
                    $errors['packagefile'] = get_string('aliasonly', 'mod_scorm');
                } else {
                    $repository = repository::get_repository_by_id($file->get_repository_id(), context_system::instance());
                    if (!$repository->supports_relative_file()) {
                        $errors['packagefile'] = get_string('repositorynotsupported', 'mod_scorm');
                    }
                }
            } else if (strtolower(substr($file->get_filename(), -3)) == 'xml') {
                $errors['packagefile'] = get_string('invalidmanifestname', 'mod_scorm');
            } else {
                // Validate this SCORM package.
                $errors = array_merge($errors, scorm_validate_package($file));
            }
        }

        return $errors;
    }
}
