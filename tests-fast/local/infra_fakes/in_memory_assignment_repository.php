<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_asyoulikeit\tests\fast\infra_fakes;

use mod_asyoulikeit\local\domain\assignment;
use mod_asyoulikeit\local\domain\assignment_repository;

/** In-memory fake mirroring the Java project's InMemoryAssignmentRepository, for fast domain tests. * @package mod_asyoulikeit
 * @package mod_asyoulikeit
 */
final class in_memory_assignment_repository implements assignment_repository {
    /** @var array<int, assignment> */
    private array $store = [];

    public function find_by_id(int $id): ?assignment {
        return $this->store[$id] ?? null;
    }

    public function save(assignment $assignment): void {
        $this->store[$assignment->id()] = $assignment;
    }
}
