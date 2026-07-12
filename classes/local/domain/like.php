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
 * A peer's like on someone else's submission. The like knows itself whether it has been
 * revoked, so "no double revoke" can be proven from this class alone, without consulting
 * a repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class like {
    /**
     * Constructs a like, defaulting to active (a freshly-given like has not been revoked).
     *
     * @param int $id
     * @param int $granterid
     * @param int $submissionid
     * @param like_status $status
     */
    public function __construct(
        /** @var int */
        private readonly int $id,
        /** @var int */
        private readonly int $granterid,
        /** @var int */
        private readonly int $submissionid,
        /** @var like_status */
        private like_status $status = like_status::ACTIVE,
    ) {
    }

    /**
     * Returns the like id.
     *
     * @return int
     */
    public function id(): int {
        return $this->id;
    }

    /**
     * Returns the id of the user who gave this like.
     *
     * @return int
     */
    public function granterid(): int {
        return $this->granterid;
    }

    /**
     * Returns the id of the submission this like is on.
     *
     * @return int
     */
    public function submissionid(): int {
        return $this->submissionid;
    }

    /**
     * Returns the current status.
     *
     * @return like_status
     */
    public function status(): like_status {
        return $this->status;
    }

    /**
     * Whether this like is currently in effect (not revoked).
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === like_status::ACTIVE;
    }

    /**
     * Revokes this like. Called only from like_service, which also checks "is it the granter"
     * and "is it still active" up front — but the final guarantee of both invariants lives here.
     *
     * @param int $requesterid
     * @return void
     */
    public function revoke(int $requesterid): void {
        if ($requesterid !== $this->granterid) {
            throw new domain_state_exception('Only the granter can revoke their own like');
        }
        if ($this->status !== like_status::ACTIVE) {
            throw new domain_state_exception("Like {$this->id} is already revoked");
        }
        $this->status = like_status::REVOKED;
    }

    /**
     * Revokes this like without checking who is asking. Called only from submission_service
     * as part of the cascade when the liked submission itself is deleted — the "only the
     * granter may revoke" invariant in {@see revoke} applies specifically to a voluntary
     * revoke by the granter, not to a revoke that happens as a consequence of the submission
     * disappearing. The "not already revoked" invariant is still shared with {@see revoke}.
     *
     * @return void
     */
    public function force_revoke(): void {
        if ($this->status !== like_status::ACTIVE) {
            throw new domain_state_exception("Like {$this->id} is already revoked");
        }
        $this->status = like_status::REVOKED;
    }
}
