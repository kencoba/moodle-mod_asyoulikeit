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
 * Main view for one AsYouLikeIt activity instance: enter, submit, like/revoke, change visibility.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/asyoulikeit/lib.php');

use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use mod_asyoulikeit\local\domain\exception\not_found_exception;
use mod_asyoulikeit\local\domain\like_service;
use mod_asyoulikeit\local\domain\submission_service;
use mod_asyoulikeit\local\domain\visibility;
use mod_asyoulikeit\local\form\submission_form;
use mod_asyoulikeit\local\infra\db_assignment_repository;
use mod_asyoulikeit\local\infra\db_like_repository;
use mod_asyoulikeit\local\infra\db_submission_repository;

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('asyoulikeit', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$moduleinstance = $DB->get_record('asyoulikeit', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/asyoulikeit:view', $context);

$viewurl = new moodle_url('/mod/asyoulikeit/view.php', ['id' => $cm->id]);

$event = \mod_asyoulikeit\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('asyoulikeit', $moduleinstance);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url($viewurl);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$assignmentrepository = new db_assignment_repository();
$submissionrepository = new db_submission_repository();
$likerepository = new db_like_repository();
$submissionservice = new submission_service($assignmentrepository, $submissionrepository, $likerepository);
$likeservice = new like_service($assignmentrepository, $submissionrepository, $likerepository);

$userid = (int) $USER->id;
$editingid = optional_param('edit', 0, PARAM_INT);

// Simple sesskey-protected actions (enter / like / revoke / changevisibility / delete).
$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '' && $action !== 'submit' && confirm_sesskey()) {
    try {
        switch ($action) {
            case 'enter':
                require_capability('mod/asyoulikeit:submit', $context);
                $assignment = $assignmentrepository->find_by_id((int) $moduleinstance->id);
                if ($assignment->is_entered_by($userid)) {
                    \core\notification::info(get_string('alreadyentered', 'mod_asyoulikeit'));
                } else {
                    $assignment->enter($userid);
                    $assignmentrepository->save($assignment);
                    \core\notification::success(get_string(
                        'entersuccess',
                        'mod_asyoulikeit',
                        \mod_asyoulikeit\local\domain\entry::INITIAL_LIKE_COUNT
                    ));
                }
                break;

            case 'like':
                require_capability('mod/asyoulikeit:like', $context);
                $submissionid = required_param('submissionid', PARAM_INT);
                $likeservice->give_like($userid, $submissionid);
                break;

            case 'revoke':
                require_capability('mod/asyoulikeit:like', $context);
                $likeid = required_param('likeid', PARAM_INT);
                $likeservice->revoke_like($likeid, $userid);
                break;

            case 'changevisibility':
                require_capability('mod/asyoulikeit:submit', $context);
                $submissionid = required_param('submissionid', PARAM_INT);
                $newvisibility = required_param('visibility', PARAM_ALPHA);
                $submission = $submissionrepository->find_by_id($submissionid);
                if ($submission === null) {
                    throw new not_found_exception("Submission not found: {$submissionid}");
                }
                $submission->change_visibility($userid, visibility::from($newvisibility));
                $submissionrepository->save($submission);
                break;

            case 'delete':
                require_capability('mod/asyoulikeit:submit', $context);
                $submissionid = required_param('submissionid', PARAM_INT);
                $submissionservice->delete($submissionid, $userid);
                \core\notification::success(get_string('deletesuccess', 'mod_asyoulikeit'));
                break;
        }
    } catch (domain_state_exception | not_found_exception $e) {
        \core\notification::error($e->getMessage());
    }

    redirect($viewurl);
}

// Submission form: only meaningful once the user has entered.
$assignment = $assignmentrepository->find_by_id((int) $moduleinstance->id);
$isentered = $assignment->is_entered_by($userid);
$cansubmit = $isentered && has_capability('mod/asyoulikeit:submit', $context);
$viewall = has_capability('mod/asyoulikeit:viewallsubmissions', $context);

$mform = null;
if ($cansubmit) {
    $editingsubmission = null;
    if ($editingid > 0) {
        $candidate = $submissionrepository->find_by_id($editingid);
        if ($candidate !== null && $candidate->submitterid() === $userid && $candidate->is_active()) {
            $editingsubmission = $candidate;
        }
    }

    $fileoptions = submission_form::file_options();
    $mform = new submission_form($viewurl);

    // Always (re-)prepare the draft area: on the first GET this stages any already-saved
    // attachments for editing; on a POST it reuses the draft itemid the form just submitted.
    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'mod_asyoulikeit',
        'submission_attachments',
        $editingsubmission?->id() ?? 0,
        $fileoptions
    );

    if ($editingsubmission !== null && !$mform->is_submitted()) {
        $mform->set_data((object) [
            'submissionid' => $editingsubmission->id(),
            'title' => $editingsubmission->title(),
            'content' => $editingsubmission->content(),
            'comment' => $editingsubmission->comment(),
            'visibility' => $editingsubmission->visibility()->value,
            'attachments' => $draftitemid,
        ]);
    } else {
        $mform->set_data((object) ['attachments' => $draftitemid]);
    }

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($data = $mform->get_data()) {
        try {
            $comment = ($data->comment ?? '') === '' ? null : $data->comment;
            if (!empty($data->submissionid)) {
                $submissionservice->edit((int) $data->submissionid, $userid, $data->title, $data->content, $comment);
                $targetsubmissionid = (int) $data->submissionid;
                \core\notification::success(get_string('editsuccess', 'mod_asyoulikeit'));
            } else {
                $submission = $submissionservice->submit(
                    (int) $moduleinstance->id,
                    $userid,
                    $data->title,
                    $data->content,
                    $comment,
                    visibility::from($data->visibility),
                );
                $targetsubmissionid = $submission->id();
                \core\notification::success(get_string('submitwork', 'mod_asyoulikeit'));
            }
            file_save_draft_area_files(
                $data->attachments,
                $context->id,
                'mod_asyoulikeit',
                'submission_attachments',
                $targetsubmissionid,
                $fileoptions
            );
        } catch (domain_state_exception | not_found_exception $e) {
            \core\notification::error($e->getMessage());
        }
        redirect($viewurl);
    }
}

// Build the submission list rows, filtered to what this viewer may see. A teacher/manager
// with mod/asyoulikeit:viewallsubmissions sees everything, published or not, so they can
// actually review the activity.
$fs = get_file_storage();
$rows = [];
foreach ($submissionservice->visible_submissions((int) $moduleinstance->id, $userid, $viewall) as $submission) {
    $activelikes = $likerepository->find_active_by_submission_id($submission->id());

    $mylikeid = null;
    foreach ($activelikes as $like) {
        if ($like->granterid() === $userid) {
            $mylikeid = $like->id();
            break;
        }
    }

    $author = \core_user::get_user($submission->submitterid());
    $isown = $submission->submitterid() === $userid;

    $attachments = [];
    $storedfiles = $fs->get_area_files(
        $context->id,
        'mod_asyoulikeit',
        'submission_attachments',
        $submission->id(),
        'filename',
        false
    );
    foreach ($storedfiles as $file) {
        $attachments[] = [
            'filename' => $file->get_filename(),
            'url' => \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false),
        ];
    }

    $rows[] = [
        'id' => $submission->id(),
        'title' => format_string($submission->title()),
        'content' => format_text($submission->content(), FORMAT_PLAIN),
        'comment' => $submission->comment() !== null ? format_text($submission->comment(), FORMAT_PLAIN) : null,
        'authorname' => $author ? fullname($author) : '?',
        'visibilitylabel' => get_string('visibility' . $submission->visibility()->value, 'mod_asyoulikeit'),
        'likecountlabel' => get_string('likecount', 'mod_asyoulikeit', count($activelikes)),
        'isown' => $isown,
        'canlike' => !$isown && $isentered && $mylikeid === null && has_capability('mod/asyoulikeit:like', $context),
        'mylikeid' => $mylikeid,
        'isprivate' => $submission->visibility() === visibility::PRIVATE,
        'ispublic' => $submission->visibility() === visibility::PUBLIC,
        'attachments' => $attachments,
        'hasattachments' => !empty($attachments),
        'editurl' => $isown ? (new \moodle_url($viewurl, ['edit' => $submission->id()]))->out(false) : null,
    ];
}

/** @var mod_asyoulikeit_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_asyoulikeit');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('asyoulikeit', $moduleinstance, $cm->id), 'generalbox mod_introbox');
}

if ($viewall) {
    $reporturl = new moodle_url('/mod/asyoulikeit/report.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($reporturl, get_string('report', 'mod_asyoulikeit'), 'get');
}

echo $renderer->entry_prompt(
    $viewurl,
    $isentered,
    $isentered ? $assignment->entry_for($userid)->likecount() : null
);

if ($mform !== null) {
    $mform->display();
}

echo $renderer->submission_list($viewurl, $rows);

echo $OUTPUT->footer();
