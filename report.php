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
 * Reviewer report: every participant, every submission, published or not.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/asyoulikeit/lib.php');

use mod_asyoulikeit\local\infra\db_assignment_repository;
use mod_asyoulikeit\local\infra\db_like_repository;
use mod_asyoulikeit\local\infra\db_submission_repository;

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('asyoulikeit', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$moduleinstance = $DB->get_record('asyoulikeit', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/asyoulikeit:viewallsubmissions', $context);

$reporturl = new moodle_url('/mod/asyoulikeit/report.php', ['id' => $cm->id]);
$PAGE->set_url($reporturl);
$PAGE->set_title(format_string($moduleinstance->name) . ': ' . get_string('report', 'mod_asyoulikeit'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$assignmentrepository = new db_assignment_repository();
$submissionrepository = new db_submission_repository();
$likerepository = new db_like_repository();

$assignment = $assignmentrepository->find_by_id((int) $moduleinstance->id);

$bysubmitter = [];
foreach ($submissionrepository->find_by_assignment_id((int) $moduleinstance->id) as $submission) {
    if ($submission->is_active()) {
        $bysubmitter[$submission->submitterid()][] = $submission;
    }
}
foreach ($bysubmitter as $submitterid => $submissions) {
    usort($submissions, fn ($a, $b) => $b->timemodified() <=> $a->timemodified());
    $bysubmitter[$submitterid] = $submissions;
}

$entries = $assignment->entries();
usort($entries, function ($a, $b) {
    $usera = \core_user::get_user($a->submitterid());
    $userb = \core_user::get_user($b->submitterid());
    return fullname($usera) <=> fullname($userb);
});

$rows = [];
foreach ($entries as $entry) {
    $submitterid = $entry->submitterid();
    $participant = \core_user::get_user($submitterid);
    $participantname = $participant ? fullname($participant) : '?';
    $submissions = $bysubmitter[$submitterid] ?? [];

    if (empty($submissions)) {
        $rows[] = [
            'participant' => $participantname,
            'issubmitted' => false,
            'statuslabel' => get_string('notsubmitted', 'mod_asyoulikeit'),
            'visibilitylabel' => '',
            'lastmodified' => '',
            'likecountlabel' => '',
        ];
        continue;
    }

    foreach ($submissions as $submission) {
        $likecount = count($likerepository->find_active_by_submission_id($submission->id()));
        $rows[] = [
            'participant' => $participantname,
            'issubmitted' => true,
            'statuslabel' => get_string('submitted', 'mod_asyoulikeit'),
            'visibilitylabel' => get_string('visibility' . $submission->visibility()->value, 'mod_asyoulikeit'),
            'lastmodified' => $submission->timemodified() > 0 ? userdate($submission->timemodified()) : '',
            'likecountlabel' => get_string('likecount', 'mod_asyoulikeit', $likecount),
        ];
    }
}

/** @var mod_asyoulikeit_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_asyoulikeit');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name) . ': ' . get_string('report', 'mod_asyoulikeit'));
echo $renderer->report_table($rows);
echo $OUTPUT->footer();
