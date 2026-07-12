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
use mod_asyoulikeit\local\domain\like_service;
use mod_asyoulikeit\local\domain\submission_service;
use mod_asyoulikeit\local\domain\visibility;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_assignment_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_like_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_submission_repository;
use PHPUnit\Framework\TestCase;

final class submission_service_test extends TestCase {
    private in_memory_assignment_repository $assignmentrepository;
    private in_memory_submission_repository $submissionrepository;
    private in_memory_like_repository $likerepository;
    private submission_service $submissionservice;
    private like_service $likeservice;

    protected function setUp(): void {
        $this->assignmentrepository = new in_memory_assignment_repository();
        $this->submissionrepository = new in_memory_submission_repository();
        $this->likerepository = new in_memory_like_repository();
        $this->submissionservice = new submission_service(
            $this->assignmentrepository,
            $this->submissionrepository,
            $this->likerepository
        );
        $this->likeservice = new like_service(
            $this->assignmentrepository,
            $this->submissionrepository,
            $this->likerepository
        );
    }

    private function newenteredassignment(int ...$submitterids): assignment {
        $assignment = new assignment(1, '課題1', '説明');
        foreach ($submitterids as $submitterid) {
            $assignment->enter($submitterid);
        }
        $this->assignmentrepository->save($assignment);
        return $assignment;
    }

    public function test_entered_submitter_can_submit(): void {
        $assignment = $this->newenteredassignment(100);

        $submission = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PRIVATE
        );

        $this->assertSame(100, $submission->submitterid());
        $this->assertSame($assignment->id(), $submission->assignmentid());
    }

    public function test_submitter_who_has_not_entered_cannot_submit(): void {
        $assignment = new assignment(1, '課題1', '説明');
        $this->assignmentrepository->save($assignment); // エントリしていない

        $this->expectException(domain_state_exception::class);
        $this->submissionservice->submit($assignment->id(), 100, 'タイトル', '内容', null, visibility::PRIVATE);
    }

    public function test_owner_can_edit_despite_existing_like(): void {
        $assignment = $this->newenteredassignment(100, 200);
        $submission = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PUBLIC
        );
        $this->likeservice->give_like(200, $submission->id());

        $this->submissionservice->edit($submission->id(), 100, '新タイトル', '新内容', '追記');

        $this->assertSame('新タイトル', $submission->title());
        $this->assertCount(1, $this->likerepository->find_active_by_submission_id($submission->id()));
    }

    public function test_deleting_a_submission_revokes_its_active_likes_and_restores_the_count(): void {
        $assignment = $this->newenteredassignment(100, 200);
        $submission = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PUBLIC
        );
        $this->likeservice->give_like(200, $submission->id());
        $before = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(4, $before->entry_for(200)->likecount());

        $this->submissionservice->delete($submission->id(), 100);

        $this->assertFalse($submission->is_active());
        $this->assertCount(0, $this->likerepository->find_active_by_submission_id($submission->id()));
        $after = $this->assignmentrepository->find_by_id($assignment->id());
        $this->assertSame(5, $after->entry_for(200)->likecount());
    }

    public function test_non_owner_cannot_delete(): void {
        $assignment = $this->newenteredassignment(100, 200);
        $submission = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PRIVATE
        );

        $this->expectException(domain_state_exception::class);
        $this->submissionservice->delete($submission->id(), 200);
    }

    public function test_visible_submissions_includes_own_private_but_hides_others_private(): void {
        $assignment = $this->newenteredassignment(100, 200);
        $mine = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PRIVATE
        );

        $forme = $this->submissionservice->visible_submissions($assignment->id(), 100);
        $forsomeoneelse = $this->submissionservice->visible_submissions($assignment->id(), 200);

        $this->assertTrue($this->containsid($forme, $mine->id()));
        $this->assertFalse($this->containsid($forsomeoneelse, $mine->id()));
    }

    public function test_visible_submissions_hides_deleted_submissions_from_everyone(): void {
        $assignment = $this->newenteredassignment(100);
        $submission = $this->submissionservice->submit(
            $assignment->id(),
            100,
            'タイトル',
            '内容',
            null,
            visibility::PUBLIC
        );
        $this->submissionservice->delete($submission->id(), 100);

        $stillvisible = $this->submissionservice->visible_submissions($assignment->id(), 100);

        $this->assertFalse($this->containsid($stillvisible, $submission->id()));
    }

    /** @param \mod_asyoulikeit\local\domain\submission[] $submissions */
    private function containsid(array $submissions, int $id): bool {
        foreach ($submissions as $submission) {
            if ($submission->id() === $id) {
                return true;
            }
        }
        return false;
    }
}
