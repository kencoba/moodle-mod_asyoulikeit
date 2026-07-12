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
 * Library of interface functions and constants for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declares which Moodle features this activity module supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return bool|string|null
 */
function asyoulikeit_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        FEATURE_GRADE_HAS_GRADE => false,
        default => null,
    };
}

/**
 * Saves a new AsYouLikeIt instance (= a new Assignment).
 *
 * @param stdClass $data Data from mod_form.
 * @param mod_asyoulikeit_mod_form|null $mform
 * @return int The id of the newly inserted record.
 */
function asyoulikeit_add_instance($data, $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    return $DB->insert_record('asyoulikeit', $data);
}

/**
 * Updates an existing AsYouLikeIt instance.
 *
 * @param stdClass $data Data from mod_form.
 * @param mod_asyoulikeit_mod_form|null $mform
 * @return bool
 */
function asyoulikeit_update_instance($data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('asyoulikeit', $data);
}

/**
 * Deletes an AsYouLikeIt instance and all of its entries/submissions/likes.
 *
 * @param int $id Instance id.
 * @return bool
 */
function asyoulikeit_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('asyoulikeit', ['id' => $id])) {
        return false;
    }

    $submissionids = $DB->get_fieldset_select(
        'asyoulikeit_submission',
        'id',
        'asyoulikeitid = ?',
        [$id]
    );
    if ($submissionids) {
        [$insql, $params] = $DB->get_in_or_equal($submissionids);
        $DB->delete_records_select('asyoulikeit_like', "submissionid $insql", $params);
    }

    $DB->delete_records('asyoulikeit_submission', ['asyoulikeitid' => $id]);
    $DB->delete_records('asyoulikeit_entry', ['asyoulikeitid' => $id]);
    $DB->delete_records('asyoulikeit', ['id' => $id]);

    return true;
}

/**
 * Serves a submission attachment, after re-checking the same "owner, active and public, or a
 * reviewer with mod/asyoulikeit:viewallsubmissions" rule the submission list itself uses.
 * Named without the "mod_" prefix, matching the legacy short form the file_pluginfile()
 * dispatcher tries before the frankenstyle-prefixed name.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function asyoulikeit_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload, array $options = []): bool {
    global $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if ($filearea !== 'submission_attachments') {
        return false;
    }

    require_login($course, false, $cm);

    $submissionid = (int) array_shift($args);
    $repository = new \mod_asyoulikeit\local\infra\db_submission_repository();
    $submission = $repository->find_by_id($submissionid);
    if ($submission === null) {
        return false;
    }

    $isownorpublic = $submission->submitterid() === (int) $USER->id
        || $submission->visibility() === \mod_asyoulikeit\local\domain\visibility::PUBLIC
        || has_capability('mod/asyoulikeit:viewallsubmissions', $context);
    if (!$submission->is_active() || !$isownorpublic) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/mod_asyoulikeit/submission_attachments/{$submissionid}/{$relativepath}";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);

    return true;
}
