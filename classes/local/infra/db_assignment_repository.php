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

use mod_asyoulikeit\local\domain\assignment;
use mod_asyoulikeit\local\domain\assignment_repository;
use mod_asyoulikeit\local\domain\entry;

/**
 * $DB-backed assignment_repository. Loads all of an instance's entries eagerly, the same
 * "small enough to not worry about scale" simplification the Java InMemoryAssignmentRepository
 * makes; revisit if a course ever has a very large number of entries in one activity.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class db_assignment_repository implements assignment_repository {
    /**
     * Finds an assignment (with its entries) by id.
     *
     * @param int $id
     * @return assignment|null
     */
    public function find_by_id(int $id): ?assignment {
        global $DB;

        $record = $DB->get_record('asyoulikeit', ['id' => $id]);
        if (!$record) {
            return null;
        }

        $entries = [];
        foreach ($DB->get_records('asyoulikeit_entry', ['asyoulikeitid' => $id]) as $entryrecord) {
            $entries[] = new entry((int) $entryrecord->userid, (int) $entryrecord->likecount);
        }

        return new assignment((int) $record->id, $record->name, $record->intro, $entries);
    }

    /**
     * Persists the assignment's current entries (upsert).
     *
     * @param assignment $assignment
     * @return void
     */
    public function save(assignment $assignment): void {
        global $DB;

        foreach ($assignment->entries() as $entry) {
            $existing = $DB->get_record('asyoulikeit_entry', [
                'asyoulikeitid' => $assignment->id(),
                'userid' => $entry->submitterid(),
            ]);
            if ($existing) {
                $existing->likecount = $entry->likecount();
                $DB->update_record('asyoulikeit_entry', $existing);
            } else {
                $DB->insert_record('asyoulikeit_entry', (object) [
                    'asyoulikeitid' => $assignment->id(),
                    'userid' => $entry->submitterid(),
                    'likecount' => $entry->likecount(),
                    'timecreated' => time(),
                ]);
            }
        }
    }
}
