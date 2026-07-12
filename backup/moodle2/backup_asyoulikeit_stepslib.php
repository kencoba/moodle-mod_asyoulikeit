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
 * Backup steps for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the complete asyoulikeit structure for backup, with id and file annotations.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_asyoulikeit_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure to be backed up.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $asyoulikeit = new backup_nested_element('asyoulikeit', ['id'], [
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        ]);

        $entries = new backup_nested_element('entries');
        $entry = new backup_nested_element('entry', ['id'], [
            'userid', 'likecount', 'timecreated',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'title', 'content', 'commenttext', 'visibility', 'status', 'timecreated', 'timemodified',
        ]);

        $likes = new backup_nested_element('likes');
        $like = new backup_nested_element('like', ['id'], [
            'granterid', 'status', 'timecreated', 'timemodified',
        ]);

        $asyoulikeit->add_child($entries);
        $entries->add_child($entry);

        $asyoulikeit->add_child($submissions);
        $submissions->add_child($submission);

        $submission->add_child($likes);
        $likes->add_child($like);

        $asyoulikeit->set_source_table('asyoulikeit', ['id' => backup::VAR_ACTIVITYID]);

        // Entries, submissions and likes are all user-generated activity data, so they are
        // only included when the "include user data" backup setting is on.
        if ($userinfo) {
            $entry->set_source_table('asyoulikeit_entry', ['asyoulikeitid' => backup::VAR_PARENTID]);
            $submission->set_source_table('asyoulikeit_submission', ['asyoulikeitid' => backup::VAR_PARENTID]);
            $like->set_source_table('asyoulikeit_like', ['submissionid' => backup::VAR_PARENTID]);
        }

        $entry->annotate_ids('user', 'userid');
        $submission->annotate_ids('user', 'userid');
        $like->annotate_ids('user', 'granterid');

        $asyoulikeit->annotate_files('mod_asyoulikeit', 'intro', null);
        $submission->annotate_files('mod_asyoulikeit', 'submission_attachments', 'id');

        return $this->prepare_activity_structure($asyoulikeit);
    }
}
