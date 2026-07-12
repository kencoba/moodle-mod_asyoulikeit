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

use mod_asyoulikeit\local\domain\like;
use mod_asyoulikeit\local\domain\like_repository;

/** In-memory fake mirroring the Java project's InMemoryLikeRepository, for fast domain tests. * @package mod_asyoulikeit
 * @package mod_asyoulikeit
 */
final class in_memory_like_repository implements like_repository {
    /** @var array<int, like> */
    private array $store = [];
    private int $nextid = 1;

    public function insert(int $granterid, int $submissionid): like {
        $like = new like($this->nextid, $granterid, $submissionid);
        $this->store[$this->nextid] = $like;
        $this->nextid++;
        return $like;
    }

    public function find_by_id(int $id): ?like {
        return $this->store[$id] ?? null;
    }

    public function find_active_by_submission_id(int $submissionid): array {
        return array_values(array_filter(
            $this->store,
            fn (like $l) => $l->submissionid() === $submissionid && $l->is_active()
        ));
    }

    public function find_active_by_granter_id(int $granterid): array {
        return array_values(array_filter(
            $this->store,
            fn (like $l) => $l->granterid() === $granterid && $l->is_active()
        ));
    }

    public function save(like $like): void {
        $this->store[$like->id()] = $like;
    }
}
