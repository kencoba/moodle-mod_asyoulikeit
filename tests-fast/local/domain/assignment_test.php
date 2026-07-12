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

use mod_asyoulikeit\local\domain\assignment;
use PHPUnit\Framework\TestCase;

final class assignment_test extends TestCase {
    public function test_entering_makes_is_entered_by_true(): void {
        $assignment = new assignment(1, '課題1', '説明');

        $this->assertFalse($assignment->is_entered_by(100));

        $assignment->enter(100);

        $this->assertTrue($assignment->is_entered_by(100));
    }

    public function test_multiple_submitters_can_enter(): void {
        $assignment = new assignment(1, '課題1', '説明');

        $assignment->enter(100);
        $assignment->enter(200);

        $this->assertTrue($assignment->is_entered_by(100));
        $this->assertTrue($assignment->is_entered_by(200));
    }

    public function test_entering_twice_is_idempotent_and_keeps_the_remaining_like_count(): void {
        $assignment = new assignment(1, '課題1', '説明');

        $assignment->enter(100);
        $assignment->entry_for(100)->consume_like();
        $assignment->enter(100);

        $this->assertSame(4, $assignment->entry_for(100)->likecount());
    }
}
