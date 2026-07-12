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
 * Port for loading/persisting assignments (and the entries they hold). Implemented against
 * Moodle's $DB by \mod_asyoulikeit\local\infra\db_assignment_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface assignment_repository {
    /**
     * Finds an assignment (with its entries) by id.
     *
     * @param int $id
     * @return assignment|null
     */
    public function find_by_id(int $id): ?assignment;

    /**
     * Persists the assignment's current entries (upsert).
     *
     * @param assignment $assignment
     * @return void
     */
    public function save(assignment $assignment): void;
}
