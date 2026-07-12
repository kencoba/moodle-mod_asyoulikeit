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

use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use mod_asyoulikeit\local\domain\exception\not_found_exception;

/**
 * Application service for giving and revoking likes.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class like_service {
    /**
     * Constructs the service.
     *
     * @param assignment_repository $assignmentrepository
     * @param submission_repository $submissionrepository
     * @param like_repository $likerepository
     */
    public function __construct(
        /** @var assignment_repository */
        private readonly assignment_repository $assignmentrepository,
        /** @var submission_repository */
        private readonly submission_repository $submissionrepository,
        /** @var like_repository */
        private readonly like_repository $likerepository,
    ) {
    }

    /**
     * Gives a like to another user's submission.
     *
     * @param int $granterid
     * @param int $submissionid
     * @return like
     */
    public function give_like(int $granterid, int $submissionid): like {
        $submission = $this->submissionrepository->find_by_id($submissionid);
        if ($submission === null) {
            throw new not_found_exception("Submission not found: {$submissionid}");
        }
        $assignment = $this->assignmentrepository->find_by_id($submission->assignmentid());
        if ($assignment === null) {
            throw new not_found_exception("Assignment not found: {$submission->assignmentid()}");
        }

        if ($submission->submitterid() === $granterid) {
            throw new domain_state_exception('Cannot like your own submission');
        }

        foreach ($this->likerepository->find_active_by_submission_id($submissionid) as $existing) {
            if ($existing->granterid() === $granterid) {
                throw new domain_state_exception(
                    "Submitter {$granterid} has already liked submission {$submissionid}"
                );
            }
        }

        $entry = $assignment->entry_for($granterid);
        if ($entry === null) {
            throw new domain_state_exception(
                "Submitter {$granterid} has not entered this assignment, cannot like"
            );
        }
        if (!$entry->can_give_like()) {
            throw new domain_state_exception("No remaining likes for submitter {$granterid}");
        }

        $entry->consume_like();
        $like = $this->likerepository->insert($granterid, $submissionid);
        $this->assignmentrepository->save($assignment);

        return $like;
    }

    /**
     * Revokes a like. Mirror image of give_like(): recovers the entry by walking
     * like -> submission -> assignment. The final guarantee of "is it the granter" / "is it
     * still active" lives in like::revoke() itself (domain layer), not here.
     *
     * @param int $likeid
     * @param int $requesterid
     * @return void
     */
    public function revoke_like(int $likeid, int $requesterid): void {
        $like = $this->likerepository->find_by_id($likeid);
        if ($like === null) {
            throw new not_found_exception("Like not found: {$likeid}");
        }
        $submission = $this->submissionrepository->find_by_id($like->submissionid());
        if ($submission === null) {
            throw new not_found_exception("Submission not found: {$like->submissionid()}");
        }
        $assignment = $this->assignmentrepository->find_by_id($submission->assignmentid());
        if ($assignment === null) {
            throw new not_found_exception("Assignment not found: {$submission->assignmentid()}");
        }
        $entry = $assignment->entry_for($like->granterid());
        if ($entry === null) {
            throw new domain_state_exception(
                "Submitter {$like->granterid()} has no entry for this assignment"
            );
        }

        $like->revoke($requesterid);
        $entry->restore_like();

        $this->likerepository->save($like);
        $this->assignmentrepository->save($assignment);
    }
}
