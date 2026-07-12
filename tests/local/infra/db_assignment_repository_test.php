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

/**
 * Tests for db_assignment_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_asyoulikeit\local\infra\db_assignment_repository
 */
final class db_assignment_repository_test extends \advanced_testcase {
    public function test_save_and_find_round_trips_entries(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');
        $instance = $generator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $repository = new db_assignment_repository();

        $this->assertNull($repository->find_by_id(0));

        $assignment = $repository->find_by_id((int) $instance->id);
        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->is_entered_by((int) $user->id));

        $assignment->enter((int) $user->id);
        $repository->save($assignment);

        $reloaded = $repository->find_by_id((int) $instance->id);
        $this->assertTrue($reloaded->is_entered_by((int) $user->id));
        $this->assertSame(5, $reloaded->entry_for((int) $user->id)->likecount());

        $reloaded->entry_for((int) $user->id)->consume_like();
        $repository->save($reloaded);

        $reloaded2 = $repository->find_by_id((int) $instance->id);
        $this->assertSame(4, $reloaded2->entry_for((int) $user->id)->likecount());
    }
}
