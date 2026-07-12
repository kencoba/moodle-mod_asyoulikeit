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
 * A user's participation in one assignment: the relationship itself turns out to carry
 * state (the remaining like budget for that assignment), which is why it is its own entity
 * rather than a plain link between user and assignment.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class entry {
    /** @var int Likes a freshly-entered user may give within one assignment. */
    public const INITIAL_LIKE_COUNT = 5;

    /** @var int Likes still available to spend within this assignment. */
    private int $likecount;

    /**
     * Constructs an entry, defaulting to a full like budget for a brand-new entry.
     *
     * @param int $submitterid The user who entered.
     * @param int|null $likecount Remaining likes, or null to use the default for a new entry.
     */
    public function __construct(
        /** @var int */
        private readonly int $submitterid,
        ?int $likecount = null,
    ) {
        $this->likecount = $likecount ?? self::INITIAL_LIKE_COUNT;
    }

    /**
     * Returns the id of the user who entered.
     *
     * @return int
     */
    public function submitterid(): int {
        return $this->submitterid;
    }

    /**
     * Returns the number of likes still available to spend within this assignment.
     *
     * @return int
     */
    public function likecount(): int {
        return $this->likecount;
    }

    /**
     * Whether this entry still has likes left to give.
     *
     * @return bool
     */
    public function can_give_like(): bool {
        return $this->likecount > 0;
    }

    /**
     * Spends one like. Called only from like_service; the invariant (>= 0) is guaranteed by
     * the caller's discipline.
     *
     * @return void
     */
    public function consume_like(): void {
        if (!$this->can_give_like()) {
            throw new domain_state_exception("No remaining likes for submitter {$this->submitterid}");
        }
        $this->likecount--;
    }

    /**
     * Returns one like to the budget. Called only from like_service. Deliberately uncapped at
     * INITIAL_LIKE_COUNT: each like can be consumed at most once and revoked at most once, so
     * the caller's discipline alone keeps this from exceeding the initial budget.
     *
     * @return void
     */
    public function restore_like(): void {
        $this->likecount++;
    }
}
