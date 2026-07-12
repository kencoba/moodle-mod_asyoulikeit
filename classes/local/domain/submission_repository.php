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

namespace mod_asyoulikeit\local\domain;

/**
 * Port for creating/loading/persisting submissions. Implemented against Moodle's $DB by
 * \mod_asyoulikeit\local\infra\db_submission_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface submission_repository {
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
    ): submission;

    /**
     * Finds a submission by id.
     *
     * @param int $id
     * @return submission|null
     */
    public function find_by_id(int $id): ?submission;

    /**
     * Finds all submissions for a given assignment.
     *
     * @param int $assignmentid
     * @return submission[]
     */
    public function find_by_assignment_id(int $assignmentid): array;

    /**
     * Persists changes to an already-inserted submission (e.g. a visibility change).
     *
     * @param submission $submission
     * @return void
     */
    public function save(submission $submission): void;
}
