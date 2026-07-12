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

use mod_asyoulikeit\local\domain\like_status;
use mod_asyoulikeit\local\domain\visibility;

/**
 * Tests for db_like_repository.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_asyoulikeit\local\infra\db_like_repository
 */
final class db_like_repository_test extends \advanced_testcase {
    public function test_insert_find_and_revoked_likes_are_excluded_from_active_lookups(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');
        $instance = $generator->create_instance(['course' => $course->id]);
        $owner = $this->getDataGenerator()->create_user();
        $granter = $this->getDataGenerator()->create_user();

        $submissionrepository = new db_submission_repository();
        $submission = $submissionrepository->insert(
            (int) $instance->id,
            (int) $owner->id,
            'タイトル',
            '内容',
            null,
            visibility::PUBLIC
        );

        $likerepository = new db_like_repository();
        $like = $likerepository->insert((int) $granter->id, $submission->id());

        $this->assertCount(1, $likerepository->find_active_by_submission_id($submission->id()));
        $this->assertCount(1, $likerepository->find_active_by_granter_id((int) $granter->id));

        $like->revoke((int) $granter->id);
        $likerepository->save($like);

        $this->assertCount(0, $likerepository->find_active_by_submission_id($submission->id()));
        $found = $likerepository->find_by_id($like->id());
        $this->assertSame(like_status::REVOKED, $found->status());
    }
}
