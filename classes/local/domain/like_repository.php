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
 * Port for creating/loading/persisting likes. Implemented against Moodle's $DB by
 * \mod_asyoulikeit\local\infra\db_like_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface like_repository {
    /**
     * Creates and persists a new ACTIVE like, returning it with its assigned id.
     *
     * @param int $granterid
     * @param int $submissionid
     * @return like
     */
    public function insert(int $granterid, int $submissionid): like;

    /**
     * Finds by id regardless of status (needed to tell whether it is already revoked).
     *
     * @param int $id
     * @return like|null
     */
    public function find_by_id(int $id): ?like;

    /**
     * Finds only ACTIVE likes on a submission — a revoked like is not "currently on" it.
     *
     * @param int $submissionid
     * @return like[]
     */
    public function find_active_by_submission_id(int $submissionid): array;

    /**
     * Finds only ACTIVE likes granted by a given user.
     *
     * @param int $granterid
     * @return like[]
     */
    public function find_active_by_granter_id(int $granterid): array;

    /**
     * Persists a status change (revoke).
     *
     * @param like $like
     * @return void
     */
    public function save(like $like): void;
}
