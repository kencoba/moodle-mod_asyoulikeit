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
 * Application service for submitting work to an assignment.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class submission_service {
    /** @var assignment_repository */
    private readonly assignment_repository $assignmentrepository;
    /** @var submission_repository */
    private readonly submission_repository $submissionrepository;
    /** @var like_repository */
    private readonly like_repository $likerepository;

    /**
     * Constructs the service.
     *
     * @param assignment_repository $assignmentrepository
     * @param submission_repository $submissionrepository
     * @param like_repository $likerepository
     */
    public function __construct(
        assignment_repository $assignmentrepository,
        submission_repository $submissionrepository,
        like_repository $likerepository,
    ) {
        $this->assignmentrepository = $assignmentrepository;
        $this->submissionrepository = $submissionrepository;
        $this->likerepository = $likerepository;
    }

    /**
     * Submits work to an assignment. Rejects submitters who have not entered.
     *
     * @param int $assignmentid
     * @param int $submitterid
     * @param string $title
     * @param string $content
     * @param string|null $comment
     * @param visibility $initialvisibility
     * @return submission
     */
    public function submit(
        int $assignmentid,
        int $submitterid,
        string $title,
        string $content,
        ?string $comment,
        visibility $initialvisibility,
    ): submission {
        $assignment = $this->assignmentrepository->find_by_id($assignmentid);
        if ($assignment === null) {
            throw new not_found_exception("Assignment not found: {$assignmentid}");
        }

        if (!$assignment->is_entered_by($submitterid)) {
            throw new domain_state_exception(
                "Submitter {$submitterid} has not entered assignment {$assignmentid}, cannot submit"
            );
        }

        return $this->submissionrepository->insert(
            $assignmentid,
            $submitterid,
            $title,
            $content,
            $comment,
            $initialvisibility,
        );
    }

    /**
     * Edits a submission's content. Owner-check happens inside submission::edit(); likes are
     * left untouched.
     *
     * @param int $submissionid
     * @param int $requesterid
     * @param string $newtitle
     * @param string $newcontent
     * @param string|null $newcomment
     * @return void
     */
    public function edit(
        int $submissionid,
        int $requesterid,
        string $newtitle,
        string $newcontent,
        ?string $newcomment,
    ): void {
        $submission = $this->submissionrepository->find_by_id($submissionid);
        if ($submission === null) {
            throw new not_found_exception("Submission not found: {$submissionid}");
        }

        $submission->edit($requesterid, $newtitle, $newcontent, $newcomment);

        $this->submissionrepository->save($submission);
    }

    /**
     * Deletes a submission. First asks submission::delete() to check "is it the owner" and
     * "is it not already deleted" — if that fails, no likes are touched. On success, every
     * currently-active like on the submission is force-revoked (bypassing the granter-identity
     * check, since this revoke happens as a consequence of the submission disappearing, not by
     * the granter's own choice) and each granter's remaining like budget is restored.
     *
     * @param int $submissionid
     * @param int $requesterid
     * @return void
     */
    public function delete(int $submissionid, int $requesterid): void {
        $submission = $this->submissionrepository->find_by_id($submissionid);
        if ($submission === null) {
            throw new not_found_exception("Submission not found: {$submissionid}");
        }

        $submission->delete($requesterid);

        $assignment = $this->assignmentrepository->find_by_id($submission->assignmentid());
        if ($assignment === null) {
            throw new not_found_exception("Assignment not found: {$submission->assignmentid()}");
        }

        foreach ($this->likerepository->find_active_by_submission_id($submissionid) as $like) {
            $like->force_revoke();
            $entry = $assignment->entry_for($like->granterid());
            if ($entry === null) {
                throw new domain_state_exception(
                    "Submitter {$like->granterid()} has no entry for this assignment"
                );
            }
            $entry->restore_like();
            $this->likerepository->save($like);
        }

        $this->assignmentrepository->save($assignment);
        $this->submissionrepository->save($submission);
    }

    /**
     * Lists submissions for an assignment as visible to a given viewer: the viewer's own
     * submissions regardless of visibility, plus everyone else's active and public
     * submissions. This is a read-side query, not a domain invariant, which is why it lives
     * here rather than on the submission entity.
     *
     * @param int $assignmentid
     * @param int $viewerid
     * @param bool $viewall Bypasses the visibility check entirely (still excludes deleted
     *     submissions). Intended for a reviewer (e.g. a teacher) who needs to see every
     *     submission, published or not, to actually review the activity.
     * @return submission[]
     */
    public function visible_submissions(int $assignmentid, int $viewerid, bool $viewall = false): array {
        $result = [];
        foreach ($this->submissionrepository->find_by_assignment_id($assignmentid) as $submission) {
            $isownorpublic = $viewall
                || $submission->submitterid() === $viewerid
                || $submission->visibility() === visibility::PUBLIC;
            if ($submission->is_active() && $isownorpublic) {
                $result[] = $submission;
            }
        }
        return $result;
    }
}
