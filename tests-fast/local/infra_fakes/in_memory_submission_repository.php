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

use mod_asyoulikeit\local\domain\submission;
use mod_asyoulikeit\local\domain\submission_repository;
use mod_asyoulikeit\local\domain\visibility;

/** In-memory fake mirroring the Java project's InMemorySubmissionRepository, for fast domain tests. * @package mod_asyoulikeit
 * @package mod_asyoulikeit
 */
final class in_memory_submission_repository implements submission_repository {
    /** @var array<int, submission> */
    private array $store = [];
    private int $nextid = 1;

    public function insert(
        int $assignmentid,
        int $submitterid,
        string $title,
        string $content,
        ?string $comment,
        visibility $visibility,
    ): submission {
        $submission = new submission(
            $this->nextid,
            $assignmentid,
            $submitterid,
            $title,
            $content,
            $comment,
            $visibility,
            \mod_asyoulikeit\local\domain\submission_status::ACTIVE,
            time()
        );
        $this->store[$this->nextid] = $submission;
        $this->nextid++;
        return $submission;
    }

    public function find_by_id(int $id): ?submission {
        return $this->store[$id] ?? null;
    }

    public function find_by_assignment_id(int $assignmentid): array {
        return array_values(array_filter(
            $this->store,
            fn (submission $s) => $s->assignmentid() === $assignmentid
        ));
    }

    public function save(submission $submission): void {
        $this->store[$submission->id()] = $submission;
    }
}
