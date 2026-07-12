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

namespace mod_asyoulikeit\tests\fast\scenario;

use PHPUnit\Framework\TestCase;

final class as_you_like_it_scenario_test extends TestCase {
    public function test_the_arden_forest_songwriting_contest_proceeds_as_scripted(): void {
        $scenario = new as_you_like_it_scenario();

        $scenario->run();

        $this->assertTrue($scenario->oliverrejectedbeforereform, '改心前のオリヴァーは提出を却下されるはず');
        $this->assertTrue($scenario->duplicatelikewasrejected, '同じ提出への二度目のLikeは却下されるはず');
        $this->assertTrue($scenario->selflikewasrejected, '自分の歌への自作自演Likeは却下されるはず');
        $this->assertTrue($scenario->revokewasaccepted, 'Likeの取り消しは受理されるはず');
        $this->assertTrue($scenario->doublerevokewasrejected, '同じLikeの二重取り消しは却下されるはず');
        $this->assertTrue($scenario->revokebynongranterwasrejected, '付与者本人以外によるLike取り消しは却下されるはず');
        $this->assertTrue($scenario->relikeafterrevokewasaccepted, '取り消した分の再Likeは受理されるはず');
        $this->assertTrue($scenario->unauthorizedvisibilitychangewasrejected, '本人以外による公開状態変更は却下されるはず');

        $submissions = $scenario->submissionrepository->find_by_assignment_id($scenario->assignmentid);
        $this->assertCount(6, $submissions, '最終的には6人全員が提出しているはず');

        $this->assertSame('public', $scenario->songs['ロザリンド']->visibility()->value);
        $this->assertSame('private', $scenario->songs['オーランド']->visibility()->value);

        $this->assertSame(2, $this->like_count_of($scenario, 'ロザリンド'), 'ロザリンドはオーランドとフィービーからLikeされる');
        $this->assertSame(1, $this->like_count_of($scenario, 'オーランド'));
        $this->assertSame(1, $this->like_count_of($scenario, 'フィービー'));
        $this->assertSame(1, $this->like_count_of($scenario, 'オリヴァー'));
        $this->assertSame(1, $this->like_count_of($scenario, 'シーリア'));
        $this->assertSame(0, $this->like_count_of($scenario, 'シルヴィアス'), 'シルヴィアスの片思いは今回も報われない');

        $assignment = $scenario->assignmentrepository->find_by_id($scenario->assignmentid);
        $orlandolikecount = $assignment->entry_for($scenario->cast['オーランド'])->likecount();
        $this->assertSame(4, $orlandolikecount, '取り消し→再Likeを経ても最終的なこの課題での残り回数は4のはず');
    }

    private function like_count_of(as_you_like_it_scenario $scenario, string $submittername): int {
        $submission = $scenario->songs[$submittername];
        return count($scenario->likerepository->find_active_by_submission_id($submission->id()));
    }
}
