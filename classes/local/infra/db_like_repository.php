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

namespace mod_asyoulikeit\local\infra;

use mod_asyoulikeit\local\domain\like;
use mod_asyoulikeit\local\domain\like_repository;
use mod_asyoulikeit\local\domain\like_status;

/**
 * $DB-backed like_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class db_like_repository implements like_repository {
    /**
     * Creates and persists a new ACTIVE like, returning it with its assigned id.
     *
     * @param int $granterid
     * @param int $submissionid
     * @return like
     */
    public function insert(int $granterid, int $submissionid): like {
        global $DB;

        $now = time();
        $id = $DB->insert_record('asyoulikeit_like', (object) [
            'submissionid' => $submissionid,
            'granterid' => $granterid,
            'status' => like_status::ACTIVE->value,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return new like($id, $granterid, $submissionid);
    }

    /**
     * Finds by id regardless of status (needed to tell whether it is already revoked).
     *
     * @param int $id
     * @return like|null
     */
    public function find_by_id(int $id): ?like {
        global $DB;

        $record = $DB->get_record('asyoulikeit_like', ['id' => $id]);
        return $record ? $this->to_domain($record) : null;
    }

    /**
     * Finds only ACTIVE likes on a submission.
     *
     * Revoked likes are kept, not deleted, so a re-like after a revoke gets its own row —
     * only ACTIVE rows count as "currently on" the submission.
     *
     * @param int $submissionid
     * @return like[]
     */
    public function find_active_by_submission_id(int $submissionid): array {
        global $DB;

        $records = $DB->get_records('asyoulikeit_like', [
            'submissionid' => $submissionid,
            'status' => like_status::ACTIVE->value,
        ]);
        return array_map([$this, 'to_domain'], array_values($records));
    }

    /**
     * Finds only ACTIVE likes granted by a given user.
     *
     * @param int $granterid
     * @return like[]
     */
    public function find_active_by_granter_id(int $granterid): array {
        global $DB;

        $records = $DB->get_records('asyoulikeit_like', [
            'granterid' => $granterid,
            'status' => like_status::ACTIVE->value,
        ]);
        return array_map([$this, 'to_domain'], array_values($records));
    }

    /**
     * Persists a status change (revoke).
     *
     * @param like $like
     * @return void
     */
    public function save(like $like): void {
        global $DB;

        $DB->update_record('asyoulikeit_like', (object) [
            'id' => $like->id(),
            'status' => $like->status()->value,
            'timemodified' => time(),
        ]);
    }

    /**
     * Maps a database record to a domain like.
     *
     * @param \stdClass $record
     * @return like
     */
    private function to_domain(\stdClass $record): like {
        return new like(
            (int) $record->id,
            (int) $record->granterid,
            (int) $record->submissionid,
            like_status::from($record->status),
        );
    }
}
