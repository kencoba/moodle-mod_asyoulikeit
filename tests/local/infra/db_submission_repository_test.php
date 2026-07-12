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

use mod_asyoulikeit\local\domain\submission_status;
use mod_asyoulikeit\local\domain\visibility;

/**
 * Tests for db_submission_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_asyoulikeit\local\infra\db_submission_repository
 */
final class db_submission_repository_test extends \advanced_testcase {
    public function test_insert_find_and_save(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');
        $instance = $generator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $repository = new db_submission_repository();

        $submission = $repository->insert(
            (int) $instance->id,
            (int) $user->id,
            'タイトル',
            '内容',
            'コメント',
            visibility::PRIVATE
        );
        $this->assertGreaterThan(0, $submission->id());

        $found = $repository->find_by_id($submission->id());
        $this->assertSame('タイトル', $found->title());
        $this->assertSame('コメント', $found->comment());
        $this->assertSame(visibility::PRIVATE, $found->visibility());

        $bylist = $repository->find_by_assignment_id((int) $instance->id);
        $this->assertCount(1, $bylist);

        $found->change_visibility((int) $user->id, visibility::PUBLIC);
        $repository->save($found);

        $reloaded = $repository->find_by_id($submission->id());
        $this->assertSame(visibility::PUBLIC, $reloaded->visibility());
    }

    public function test_empty_comment_round_trips_as_null(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');
        $instance = $generator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $repository = new db_submission_repository();
        $submission = $repository->insert(
            (int) $instance->id,
            (int) $user->id,
            'タイトル',
            '内容',
            null,
            visibility::PRIVATE
        );

        $found = $repository->find_by_id($submission->id());
        $this->assertNull($found->comment());
    }

    public function test_edit_and_delete_are_persisted(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');
        $instance = $generator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $repository = new db_submission_repository();
        $submission = $repository->insert(
            (int) $instance->id,
            (int) $user->id,
            'タイトル',
            '内容',
            null,
            visibility::PRIVATE
        );

        $submission->edit((int) $user->id, '新タイトル', '新内容', '追記');
        $repository->save($submission);

        $editedreload = $repository->find_by_id($submission->id());
        $this->assertSame('新タイトル', $editedreload->title());
        $this->assertSame('新内容', $editedreload->content());
        $this->assertSame('追記', $editedreload->comment());
        $this->assertSame(submission_status::ACTIVE, $editedreload->status());

        $submission->delete((int) $user->id);
        $repository->save($submission);

        $deletedreload = $repository->find_by_id($submission->id());
        $this->assertSame(submission_status::DELETED, $deletedreload->status());
        $this->assertFalse($deletedreload->is_active());
    }
}
