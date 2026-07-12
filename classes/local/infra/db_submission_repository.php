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

namespace mod_asyoulikeit\local\infra;

use mod_asyoulikeit\local\domain\submission;
use mod_asyoulikeit\local\domain\submission_repository;
use mod_asyoulikeit\local\domain\submission_status;
use mod_asyoulikeit\local\domain\visibility;

/**
 * $DB-backed submission_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class db_submission_repository implements submission_repository {
    /**
     * Creates and persists a new submission, returning it with its assigned id.
     *
     * @param int $assignmentid
     * @param int $submitterid
     * @param string $title
     * @param string $content
     * @param string|null $comment
     * @param visibility $visibility
     * @return submission
     */
    public function insert(
        int $assignmentid,
        int $submitterid,
        string $title,
        string $content,
        ?string $comment,
        visibility $visibility,
    ): submission {
        global $DB;

        $now = time();
        $id = $DB->insert_record('asyoulikeit_submission', (object) [
            'asyoulikeitid' => $assignmentid,
            'userid' => $submitterid,
            'title' => $title,
            'content' => $content,
            'commenttext' => $comment,
            'visibility' => $visibility->value,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return new submission(
            $id,
            $assignmentid,
            $submitterid,
            $title,
            $content,
            $comment,
            $visibility,
            submission_status::ACTIVE,
            $now
        );
    }

    /**
     * Finds a submission by id.
     *
     * @param int $id
     * @return submission|null
     */
    public function find_by_id(int $id): ?submission {
        global $DB;

        $record = $DB->get_record('asyoulikeit_submission', ['id' => $id]);
        return $record ? $this->to_domain($record) : null;
    }

    /**
     * Finds all submissions for a given assignment.
     *
     * @param int $assignmentid
     * @return submission[]
     */
    public function find_by_assignment_id(int $assignmentid): array {
        global $DB;

        $records = $DB->get_records('asyoulikeit_submission', ['asyoulikeitid' => $assignmentid]);
        return array_map([$this, 'to_domain'], array_values($records));
    }

    /**
     * Persists all of the submission's current mutable state (title, content, comment,
     * visibility, status).
     *
     * @param submission $submission
     * @return void
     */
    public function save(submission $submission): void {
        global $DB;

        $DB->update_record('asyoulikeit_submission', (object) [
            'id' => $submission->id(),
            'title' => $submission->title(),
            'content' => $submission->content(),
            'commenttext' => $submission->comment(),
            'visibility' => $submission->visibility()->value,
            'status' => $submission->status()->value,
            'timemodified' => time(),
        ]);
    }

    /**
     * Maps a database record to a domain submission.
     *
     * @param \stdClass $record
     * @return submission
     */
    private function to_domain(\stdClass $record): submission {
        return new submission(
            (int) $record->id,
            (int) $record->asyoulikeitid,
            (int) $record->userid,
            $record->title,
            $record->content,
            ($record->commenttext ?? '') === '' ? null : $record->commenttext,
            visibility::from($record->visibility),
            submission_status::from($record->status),
            (int) $record->timemodified,
        );
    }
}
