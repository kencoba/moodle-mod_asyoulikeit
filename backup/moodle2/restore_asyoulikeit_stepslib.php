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
 * Restore steps for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the complete asyoulikeit structure for restore, mirroring the backup structure.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_asyoulikeit_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the structure to be restored.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('asyoulikeit', '/activity/asyoulikeit');

        if ($userinfo) {
            $paths[] = new restore_path_element('asyoulikeit_entry', '/activity/asyoulikeit/entries/entry');
            $paths[] = new restore_path_element(
                'asyoulikeit_submission',
                '/activity/asyoulikeit/submissions/submission'
            );
            $paths[] = new restore_path_element(
                'asyoulikeit_like',
                '/activity/asyoulikeit/submissions/submission/likes/like'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes an asyoulikeit element (the activity instance itself).
     *
     * @param array $data
     * @return void
     */
    protected function process_asyoulikeit($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('asyoulikeit', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Processes an entry element.
     *
     * @param array $data
     * @return void
     */
    protected function process_asyoulikeit_entry($data) {
        global $DB;

        $data = (object) $data;
        $data->asyoulikeitid = $this->get_new_parentid('asyoulikeit');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('asyoulikeit_entry', $data);
    }

    /**
     * Processes a submission element.
     *
     * @param array $data
     * @return void
     */
    protected function process_asyoulikeit_submission($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->asyoulikeitid = $this->get_new_parentid('asyoulikeit');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('asyoulikeit_submission', $data);
        $this->set_mapping('asyoulikeit_submission', $oldid, $newitemid, true);
    }

    /**
     * Processes a like element.
     *
     * @param array $data
     * @return void
     */
    protected function process_asyoulikeit_like($data) {
        global $DB;

        $data = (object) $data;
        $data->submissionid = $this->get_new_parentid('asyoulikeit_submission');
        $data->granterid = $this->get_mappingid('user', $data->granterid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('asyoulikeit_like', $data);
    }

    /**
     * Restores the files attached to the activity intro and to each submission.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_asyoulikeit', 'intro', null);
        $this->add_related_files('mod_asyoulikeit', 'submission_attachments', 'asyoulikeit_submission');
    }
}
