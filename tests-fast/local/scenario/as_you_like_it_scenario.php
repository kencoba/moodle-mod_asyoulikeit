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

use mod_asyoulikeit\local\domain\assignment;
use mod_asyoulikeit\local\domain\assignment_repository;
use mod_asyoulikeit\local\domain\exception\domain_state_exception;
use mod_asyoulikeit\local\domain\like;
use mod_asyoulikeit\local\domain\like_repository;
use mod_asyoulikeit\local\domain\like_service;
use mod_asyoulikeit\local\domain\submission;
use mod_asyoulikeit\local\domain\submission_repository;
use mod_asyoulikeit\local\domain\submission_service;
use mod_asyoulikeit\local\domain\visibility;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_assignment_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_like_repository;
use mod_asyoulikeit\tests\fast\infra_fakes\in_memory_submission_repository;

/**
 * Ported from the Java project's submission.scenario.AsYouLikeItScenario: the cast of
 * Shakespeare's "As You Like It" enters an assignment ("作詞", songwriting), submits their
 * work, and exchanges likes in the Forest of Arden. Kept as a living illustration of the
 * domain rules, same as in the Java and Lean4 ports.
 * @package mod_asyoulikeit
 */
final class as_you_like_it_scenario {
    public readonly assignment_repository $assignmentrepository;
    public readonly submission_repository $submissionrepository;
    public readonly like_repository $likerepository;

    private readonly submission_service $submissionservice;
    private readonly like_service $likeservice;

    /** @var array<string, int> Character name => userid. */
    public array $cast = [];
    public int $assignmentid;
    /** @var array<string, submission> Character name => their submission. */
    public array $songs = [];

    public bool $oliverrejectedbeforereform = false;
    public bool $duplicatelikewasrejected = false;
    public bool $selflikewasrejected = false;
    public bool $revokewasaccepted = false;
    public bool $doublerevokewasrejected = false;
    public bool $revokebynongranterwasrejected = false;
    public bool $relikeafterrevokewasaccepted = false;
    public bool $unauthorizedvisibilitychangewasrejected = false;

    private ?like $orlandotorosalindlike = null;
    private ?like $silviustophoebelike = null;

    private int $nextuserid = 1;

    public function __construct() {
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

    public function run(): void {
        $this->cast_characters();
        $assignment = $this->announce_assignment();
        $this->open_entries($assignment);

        $this->oliver_tries_to_submit_too_soon($assignment);
        $this->submit_songs($assignment);
        $this->oliver_reforms_and_submits($assignment);
        $this->exchange_likes();
        $this->revoke_and_relike();
        $this->manage_visibility();
    }

    private function cast_characters(): void {
        foreach (['オーランド', 'ロザリンド', 'オリヴァー', 'シーリア', 'シルヴィアス', 'フィービー'] as $name) {
            $this->cast[$name] = $this->nextuserid++;
        }
    }

    private function announce_assignment(): assignment {
        $assignment = new assignment(1, '作詞', 'アーデンの森で、あなたの持ち歌を作ってください');
        $this->assignmentrepository->save($assignment);
        $this->assignmentid = $assignment->id();
        return $assignment;
    }

    private function open_entries(assignment $assignment): void {
        foreach (['オーランド', 'ロザリンド', 'シーリア', 'シルヴィアス', 'フィービー'] as $name) {
            $assignment->enter($this->cast[$name]);
        }
        // オリヴァーはまだ兄弟の確執を抱えたままで、エントリしていない。
    }

    private function oliver_tries_to_submit_too_soon(assignment $assignment): void {
        try {
            $this->submissionservice->submit(
                $assignment->id(),
                $this->cast['オリヴァー'],
                'まだ書きかけの歌',
                '……',
                null,
                visibility::PRIVATE
            );
            throw new \LogicException('エントリしていないのに提出できてしまった');
        } catch (domain_state_exception $e) {
            $this->oliverrejectedbeforereform = true;
        }
    }

    private function submit_songs(assignment $assignment): void {
        $this->submit_one(
            $assignment,
            'オーランド',
            '君の名を木々に',
            '森の木という木に、僕は君の名を刻みつける。',
            'ロザリンドへ。想いを止められなくて、木々に頼んでしまった。'
        );
        $this->submit_one(
            $assignment,
            'ロザリンド',
            'ガニミードの独り言',
            '男装の少年は、誰にも言えない秘密の恋を抱えている。',
            '変装は、時に本音を隠す一番の方法。'
        );
        $this->submit_one(
            $assignment,
            'シーリア',
            '従姉妹への手紙',
            'あなたが笑えば、私も笑う。それだけで十分だった。',
            'ロザリンド、いつも隣にいるからね。'
        );
        $this->submit_one(
            $assignment,
            'シルヴィアス',
            '羊飼いの嘆き',
            '冷たいまなざしさえも、僕には甘い痛みだ。',
            'フィービー、届かなくてもいい。歌うことだけが救いだから。'
        );
        $this->submit_one(
            $assignment,
            'フィービー',
            'ガニミードに寄せて',
            'シルヴィアスには興味がない。あの見知らぬ若者のことばかり考えてしまう。',
            'シルヴィアス、ごめんなさい。でも心は正直なの。'
        );
    }

    private function oliver_reforms_and_submits(assignment $assignment): void {
        $assignment->enter($this->cast['オリヴァー']);
        $this->submit_one(
            $assignment,
            'オリヴァー',
            '贖罪の歌',
            '兄弟を憎んだ日々よ、さようなら。森の光が僕を赦してくれた。',
            'オーランド、今まですまなかった。'
        );
    }

    private function submit_one(
        assignment $assignment,
        string $name,
        string $title,
        string $content,
        string $comment
    ): void {
        $submission = $this->submissionservice->submit(
            $assignment->id(),
            $this->cast[$name],
            $title,
            $content,
            $comment,
            visibility::PRIVATE
        );
        $this->songs[$name] = $submission;
    }

    private function exchange_likes(): void {
        $this->orlandotorosalindlike = $this->give_like('オーランド', 'ロザリンド');
        $this->give_like('ロザリンド', 'オーランド');
        $this->silviustophoebelike = $this->give_like('シルヴィアス', 'フィービー');
        $this->give_like('フィービー', 'ロザリンド');
        $this->give_like('シーリア', 'オリヴァー');
        $this->give_like('オリヴァー', 'シーリア');

        $orlandoid = $this->cast['オーランド'];
        $rosalindsong = $this->songs['ロザリンド'];
        $orlandosong = $this->songs['オーランド'];

        try {
            $this->likeservice->give_like($orlandoid, $rosalindsong->id());
            throw new \LogicException('同じ提出に二度Likeできてしまった');
        } catch (domain_state_exception $e) {
            $this->duplicatelikewasrejected = true;
        }

        try {
            $this->likeservice->give_like($orlandoid, $orlandosong->id());
            throw new \LogicException('自分の歌に自分でLikeできてしまった');
        } catch (domain_state_exception $e) {
            $this->selflikewasrejected = true;
        }
    }

    private function give_like(string $grantername, string $targetname): like {
        return $this->likeservice->give_like($this->cast[$grantername], $this->songs[$targetname]->id());
    }

    private function revoke_and_relike(): void {
        $orlandoid = $this->cast['オーランド'];
        $rosalindsong = $this->songs['ロザリンド'];

        $this->likeservice->revoke_like($this->orlandotorosalindlike->id(), $orlandoid);
        $this->revokewasaccepted = true;

        try {
            $this->likeservice->revoke_like($this->orlandotorosalindlike->id(), $orlandoid);
            throw new \LogicException('同じLikeを二度取り消せてしまった');
        } catch (domain_state_exception $e) {
            $this->doublerevokewasrejected = true;
        }

        try {
            $this->likeservice->revoke_like($this->silviustophoebelike->id(), $orlandoid);
            throw new \LogicException('付与者本人以外がLikeを取り消せてしまった');
        } catch (domain_state_exception $e) {
            $this->revokebynongranterwasrejected = true;
        }

        $this->likeservice->give_like($orlandoid, $rosalindsong->id());
        $this->relikeafterrevokewasaccepted = true;
    }

    private function manage_visibility(): void {
        $rosalindid = $this->cast['ロザリンド'];
        $rosalindsong = $this->songs['ロザリンド'];
        $rosalindsong->change_visibility($rosalindid, visibility::PUBLIC);
        $this->submissionrepository->save($rosalindsong);

        $oliverid = $this->cast['オリヴァー'];
        try {
            $rosalindsong->change_visibility($oliverid, visibility::PRIVATE);
            throw new \LogicException('本人以外が公開状態を変更できてしまった');
        } catch (domain_state_exception $e) {
            $this->unauthorizedvisibilitychangewasrejected = true;
        }
    }
}
