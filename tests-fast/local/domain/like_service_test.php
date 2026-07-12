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
use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use mod_asyoulikeit\local\domain\exception\not_found_exception;
use mod_asyoulikeit\local\domain\like_service;
use mod_asyoulikeit\local\domain\submission;
use mod_asyoulikeit\local\domain\visibility;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_assignment_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_like_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_submission_repository;
use PHPUnit\Framework\TestCase;

final class like_service_test extends TestCase {
    private in_memory_assignment_repository $assignmentrepository;
    private in_memory_submission_repository $submissionrepository;
    private in_memory_like_repository $likerepository;
    private like_service $likeservice;

    protected function setUp(): void {
        $this->assignmentrepository = new in_memory_assignment_repository();
        $this->submissionrepository = new in_memory_submission_repository();
        $this->likerepository = new in_memory_like_repository();
        $this->likeservice = new like_service(
            $this->assignmentrepository,
            $this->submissionrepository,
            $this->likerepository
        );
    }

    private function create_assignment(): assignment {
        return new assignment(1, '課題', '説明');
    }

    private function create_submission(assignment $assignment, int $ownerid): submission {
        return $this->submissionrepository->insert(
            $assignment->id(),
            $ownerid,
            'タイトル',
            '内容',
            null,
            visibility::PUBLIC
        );
    }

    private function exhaust_likes(assignment $assignment, int $submitterid): void {
        $entry = $assignment->entry_for($submitterid);
        while ($entry->can_give_like()) {
            $entry->consume_like();
        }
    }

    public function test_can_like_someone_elses_submission(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);

        $like = $this->likeservice->give_like($granterid, $submission->id());

        $this->assertSame($granterid, $like->granterid());
        $this->assertSame($submission->id(), $like->submissionid());
        $updated = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(4, $updated->entry_for($granterid)->likecount());
        $this->assertCount(1, $this->likerepository->find_active_by_submission_id($submission->id()));
    }

    public function test_cannot_like_own_submission(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);

        $this->expectException(domain_state_exception::class);
        $this->likeservice->give_like($ownerid, $submission->id());
    }

    public function test_cannot_like_the_same_submission_twice(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);
        $this->likeservice->give_like($granterid, $submission->id());

        try {
            $this->likeservice->give_like($granterid, $submission->id());
            $this->fail('duplicate like should have been rejected');
        } catch (domain_state_exception $e) {
            // Expected.
        }

        $this->assertCount(1, $this->likerepository->find_active_by_submission_id($submission->id()));
        $updated = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(4, $updated->entry_for($granterid)->likecount());
    }

    public function test_cannot_like_when_out_of_likes(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->exhaust_likes($assignment, $granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);

        $this->expectException(domain_state_exception::class);
        $this->likeservice->give_like($granterid, $submission->id());
    }

    public function test_submitter_who_has_not_entered_cannot_like(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);

        $this->expectException(domain_state_exception::class);
        $this->likeservice->give_like($granterid, $submission->id());
    }

    public function test_cannot_like_a_nonexistent_submission(): void {
        $this->expectException(not_found_exception::class);
        $this->likeservice->give_like(200, 999);
    }

    public function test_granter_can_revoke_their_like_and_the_count_recovers(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);
        $like = $this->likeservice->give_like($granterid, $submission->id());

        $this->likeservice->revoke_like($like->id(), $granterid);

        $updated = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(5, $updated->entry_for($granterid)->likecount());
        $this->assertCount(0, $this->likerepository->find_active_by_submission_id($submission->id()));
    }

    public function test_cannot_revoke_the_same_like_twice(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);
        $like = $this->likeservice->give_like($granterid, $submission->id());
        $this->likeservice->revoke_like($like->id(), $granterid);

        $this->expectException(domain_state_exception::class);
        $this->likeservice->revoke_like($like->id(), $granterid);
    }

    public function test_only_the_granter_can_revoke_their_like(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $someoneelseid = 300;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);
        $like = $this->likeservice->give_like($granterid, $submission->id());

        $this->expectException(domain_state_exception::class);
        $this->likeservice->revoke_like($like->id(), $someoneelseid);
    }

    public function test_can_re_like_after_revoking(): void {
        $assignment = $this->create_assignment();
        $ownerid = 100;
        $granterid = 200;
        $assignment->enter($granterid);
        $this->assignmentrepository->save($assignment);
        $submission = $this->create_submission($assignment, $ownerid);
        $like = $this->likeservice->give_like($granterid, $submission->id());
        $this->likeservice->revoke_like($like->id(), $granterid);

        $again = $this->likeservice->give_like($granterid, $submission->id());

        $this->assertSame($granterid, $again->granterid());
        $updated = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(4, $updated->entry_for($granterid)->likecount());
        $this->assertCount(1, $this->likerepository->find_active_by_submission_id($submission->id()));
    }

    public function test_cannot_revoke_a_nonexistent_like(): void {
        $this->expectException(not_found_exception::class);
        $this->likeservice->revoke_like(999, 200);
    }
}
