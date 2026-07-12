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

use mod_asyoulikeit\local\domain\entry;
use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use PHPUnit\Framework\TestCase;

final class entry_test extends TestCase {
    public function test_new_entry_has_five_likes(): void {
        $entry = new entry(1);

        $this->assertSame(5, $entry->likecount());
        $this->assertTrue($entry->can_give_like());
    }

    public function test_consuming_a_like_decrements_the_count(): void {
        $entry = new entry(1);

        $entry->consume_like();

        $this->assertSame(4, $entry->likecount());
    }

    public function test_cannot_give_a_like_when_count_is_zero(): void {
        $entry = new entry(1, 0);

        $this->assertFalse($entry->can_give_like());
        $this->expectException(domain_state_exception::class);
        $entry->consume_like();
    }

    public function test_restoring_a_like_increments_the_count(): void {
        $entry = new entry(1);

        $entry->consume_like();
        $entry->restore_like();

        $this->assertSame(5, $entry->likecount());
    }
}
