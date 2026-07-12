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
 * One AsYouLikeIt activity instance, holding the entry (participation) of every user
 * who has entered it.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment {
    /** @var array<int, entry> Keyed by submitterid. */
    private array $entries = [];

    /**
     * Constructs an assignment, optionally pre-loaded with existing entries.
     *
     * @param int $id
     * @param string $title
     * @param string $description
     * @param entry[] $entries
     */
    public function __construct(
        /** @var int */
        private readonly int $id,
        /** @var string */
        private readonly string $title,
        /** @var string */
        private readonly string $description,
        array $entries = [],
    ) {
        foreach ($entries as $entry) {
            $this->entries[$entry->submitterid()] = $entry;
        }
    }

    /**
     * Returns the assignment id.
     *
     * @return int
     */
    public function id(): int {
        return $this->id;
    }

    /**
     * Returns the assignment title.
     *
     * @return string
     */
    public function title(): string {
        return $this->title;
    }

    /**
     * Returns the assignment description.
     *
     * @return string
     */
    public function description(): string {
        return $this->description;
    }

    /**
     * Returns every entry currently held by this assignment.
     *
     * @return entry[]
     */
    public function entries(): array {
        return array_values($this->entries);
    }

    /**
     * Returns the entry for a given user, if they have entered.
     *
     * @param int $submitterid
     * @return entry|null
     */
    public function entry_for(int $submitterid): ?entry {
        return $this->entries[$submitterid] ?? null;
    }

    /**
     * Whether the given user has entered this assignment.
     *
     * @param int $submitterid
     * @return bool
     */
    public function is_entered_by(int $submitterid): bool {
        return array_key_exists($submitterid, $this->entries);
    }

    /**
     * Enters the given user into this assignment. Idempotent: entering twice leaves the
     * existing entry (and its remaining like count) untouched.
     *
     * @param int $submitterid
     * @return void
     */
    public function enter(int $submitterid): void {
        if (!$this->is_entered_by($submitterid)) {
            $this->entries[$submitterid] = new entry($submitterid);
        }
    }
}
