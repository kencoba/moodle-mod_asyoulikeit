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

namespace mod_asyoulikeit\tests\fast\domain;

use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use mod_asyoulikeit\local\domain\submission;
use mod_asyoulikeit\local\domain\visibility;
use PHPUnit\Framework\TestCase;

/**
 * Note: the Java version also asserts that Submission's constructor rejects a SubmittedWork
 * whose ownerId differs from the submitterId. That check doesn't carry over here because this
 * port dropped the separate SubmittedWork value object (see the plan's "primitives simplification"
 * decision) — submission() only ever takes a single submitterid, so the mismatch it guarded
 * against is no longer representable.
 * @package mod_asyoulikeit
 */
final class submission_test extends TestCase {
    private function new_submission(int $submitterid): submission {
        return new submission(1, 10, $submitterid, 'タイトル', '内容', 'がんばりました', visibility::PRIVATE);
    }

    public function test_owner_can_change_visibility(): void {
        $submission = $this->new_submission(100);

        $submission->change_visibility(100, visibility::PUBLIC);

        $this->assertSame(visibility::PUBLIC, $submission->visibility());
    }

    public function test_non_owner_cannot_change_visibility(): void {
        $submission = $this->new_submission(100);

        $this->expectException(domain_state_exception::class);
        $submission->change_visibility(200, visibility::PUBLIC);
    }

    public function test_owner_can_edit_content_regardless_of_likes(): void {
        $submission = $this->new_submission(100);

        $submission->edit(100, '新タイトル', '新内容', '書き直しました');

        $this->assertSame('新タイトル', $submission->title());
        $this->assertSame('新内容', $submission->content());
        $this->assertSame('書き直しました', $submission->comment());
    }

    public function test_non_owner_cannot_edit(): void {
        $submission = $this->new_submission(100);

        $this->expectException(domain_state_exception::class);
        $submission->edit(200, '改題', '内容', null);
    }

    public function test_owner_can_delete(): void {
        $submission = $this->new_submission(100);

        $submission->delete(100);

        $this->assertFalse($submission->is_active());
    }

    public function test_non_owner_cannot_delete(): void {
        $submission = $this->new_submission(100);

        $this->expectException(domain_state_exception::class);
        $submission->delete(200);
    }

    public function test_cannot_delete_twice(): void {
        $submission = $this->new_submission(100);
        $submission->delete(100);

        $this->expectException(domain_state_exception::class);
        $submission->delete(100);
    }
}
