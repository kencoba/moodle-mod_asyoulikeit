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
 * Lists all AsYouLikeIt activity instances in a course.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.
$course = get_course($id);

require_course_login($course);

$context = context_course::instance($course->id);
$PAGE->set_url('/mod/asyoulikeit/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_asyoulikeit'));

$modinfo = get_fast_modinfo($course);
$instances = $modinfo->get_instances_of('asyoulikeit');

if (!$instances) {
    echo $OUTPUT->notification(get_string('noinstances', 'mod_asyoulikeit'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('name')];
    foreach ($instances as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        $table->data[] = [
            html_writer::link(
                new moodle_url('/mod/asyoulikeit/view.php', ['id' => $cm->id]),
                format_string($cm->name)
            ),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
