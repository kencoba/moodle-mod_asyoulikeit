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

/**
 * One user's submitted work for an assignment.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class submission {
    /**
     * Constructs a submission.
     *
     * @param int $id
     * @param int $assignmentid
     * @param int $submitterid
     * @param string $title
     * @param string $content
     * @param string|null $comment
     * @param visibility $visibility
     * @param submission_status $status
     * @param int $timemodified Unix timestamp; 0 if unknown (e.g. not yet persisted).
     */
    public function __construct(
        /** @var int */
        private readonly int $id,
        /** @var int */
        private readonly int $assignmentid,
        /** @var int */
        private readonly int $submitterid,
        /** @var string */
        private string $title,
        /** @var string */
        private string $content,
        /** @var string|null */
        private ?string $comment,
        /** @var visibility */
        private visibility $visibility,
        /** @var submission_status */
        private submission_status $status = submission_status::ACTIVE,
        /** @var int */
        private readonly int $timemodified = 0,
    ) {
    }

    /**
     * Returns the submission id.
     *
     * @return int
     */
    public function id(): int {
        return $this->id;
    }

    /**
     * Returns the id of the assignment this submission belongs to.
     *
     * @return int
     */
    public function assignmentid(): int {
        return $this->assignmentid;
    }

    /**
     * Returns the id of the user who submitted this work.
     *
     * @return int
     */
    public function submitterid(): int {
        return $this->submitterid;
    }

    /**
     * Returns the submission title.
     *
     * @return string
     */
    public function title(): string {
        return $this->title;
    }

    /**
     * Returns the submission content.
     *
     * @return string
     */
    public function content(): string {
        return $this->content;
    }

    /**
     * Returns the submitter's optional comment.
     *
     * @return string|null
     */
    public function comment(): ?string {
        return $this->comment;
    }

    /**
     * Returns the current visibility.
     *
     * @return visibility
     */
    public function visibility(): visibility {
        return $this->visibility;
    }

    /**
     * Changes the visibility. Only the submission's own owner may do this; enforced here,
     * not just by callers.
     *
     * @param int $requesterid
     * @param visibility $newvisibility
     * @return void
     */
    public function change_visibility(int $requesterid, visibility $newvisibility): void {
        if ($requesterid !== $this->submitterid) {
            throw new domain_state_exception('Only the submission owner can change its visibility');
        }
        $this->visibility = $newvisibility;
    }

    /**
     * Returns the current status.
     *
     * @return submission_status
     */
    public function status(): submission_status {
        return $this->status;
    }

    /**
     * Whether this submission is still active (not deleted).
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === submission_status::ACTIVE;
    }

    /**
     * Returns when this submission was last saved (created or edited), as a Unix timestamp.
     * 0 if the submission was constructed without one (e.g. not yet persisted).
     *
     * @return int
     */
    public function timemodified(): int {
        return $this->timemodified;
    }

    /**
     * Edits the submission's content. Only the owner may do this; enforced here, not just by
     * callers. Deliberately does not check for existing likes — content is allowed to diverge
     * from what a like was given against. Does not touch visibility, which has its own
     * dedicated method.
     *
     * @param int $requesterid
     * @param string $newtitle
     * @param string $newcontent
     * @param string|null $newcomment
     * @return void
     */
    public function edit(int $requesterid, string $newtitle, string $newcontent, ?string $newcomment): void {
        if ($requesterid !== $this->submitterid) {
            throw new domain_state_exception('Only the submission owner can edit it');
        }
        $this->title = $newtitle;
        $this->content = $newcontent;
        $this->comment = $newcomment;
    }

    /**
     * Deletes the submission (soft-delete). Only the owner may do this, and only once;
     * enforced here, not just by callers — the same two-guard shape as {@see like::revoke}.
     * Does not touch any likes on this submission; cascading their revocation is the
     * responsibility of submission_service.
     *
     * @param int $requesterid
     * @return void
     */
    public function delete(int $requesterid): void {
        if ($requesterid !== $this->submitterid) {
            throw new domain_state_exception('Only the submission owner can delete it');
        }
        if ($this->status !== submission_status::ACTIVE) {
            throw new domain_state_exception("Submission {$this->id} is already deleted");
        }
        $this->status = submission_status::DELETED;
    }
}
