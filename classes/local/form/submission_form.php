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

namespace mod_asyoulikeit\local\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form used by an entered user to submit their work for an assignment.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_form extends \moodleform {
    /**
     * Defines the form fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'submissionheader', get_string('submitwork', 'mod_asyoulikeit'));

        // 0 means "creating a new submission"; view.php sets this to a real id when editing.
        $mform->addElement('hidden', 'submissionid', 0);
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('submissiontitle', 'mod_asyoulikeit'), ['size' => '64']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'content',
            get_string('submissioncontent', 'mod_asyoulikeit'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('content', PARAM_TEXT);
        $mform->addRule('content', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'comment',
            get_string('submissioncomment', 'mod_asyoulikeit'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('comment', PARAM_TEXT);

        $mform->addElement('select', 'visibility', get_string('submissionvisibility', 'mod_asyoulikeit'), [
            'private' => get_string('visibilityprivate', 'mod_asyoulikeit'),
            'public' => get_string('visibilitypublic', 'mod_asyoulikeit'),
        ]);
        $mform->setDefault('visibility', 'private');
        // Ignored when editing an existing submission — visibility changes go through the
        // dedicated per-row control instead (see view.php), matching the Lean4/Java "edit"
        // operation, which does not touch visibility either.

        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('submissionattachments', 'mod_asyoulikeit'),
            null,
            self::file_options()
        );

        $this->add_action_buttons(false, get_string('submitwork', 'mod_asyoulikeit'));
    }

    /**
     * Options shared between the filemanager element and the draft-area preparation/save
     * calls in view.php — they must match or Moodle will reject the upload.
     *
     * @return array
     */
    public static function file_options(): array {
        global $CFG;

        return [
            'subdirs' => 0,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 5,
            'accepted_types' => '*',
        ];
    }
}
