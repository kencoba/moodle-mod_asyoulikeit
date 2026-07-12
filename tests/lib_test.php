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

namespace mod_asyoulikeit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/asyoulikeit/lib.php');

/**
 * Tests for mod_asyoulikeit's lib.php callbacks.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      ::asyoulikeit_add_instance
 * @covers      ::asyoulikeit_update_instance
 * @covers      ::asyoulikeit_delete_instance
 * @covers      ::asyoulikeit_supports
 */
final class lib_test extends \advanced_testcase {
    public function test_add_update_delete_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_asyoulikeit');

        $instance = $generator->create_instance(['course' => $course->id, 'name' => 'テスト課題']);
        $this->assertTrue($DB->record_exists('asyoulikeit', ['id' => $instance->id]));

        $updatedata = (object) [
            'instance' => $instance->id,
            'course' => $course->id,
            'name' => '更新後の課題名',
            'intro' => '',
            'introformat' => FORMAT_HTML,
        ];
        \asyoulikeit_update_instance($updatedata);

        $record = $DB->get_record('asyoulikeit', ['id' => $instance->id]);
        $this->assertSame('更新後の課題名', $record->name);

        \asyoulikeit_delete_instance($instance->id);
        $this->assertFalse($DB->record_exists('asyoulikeit', ['id' => $instance->id]));
    }

    public function test_supports(): void {
        $this->assertTrue(\asyoulikeit_supports(FEATURE_MOD_INTRO));
        $this->assertSame(MOD_PURPOSE_COLLABORATION, \asyoulikeit_supports(FEATURE_MOD_PURPOSE));
        $this->assertFalse(\asyoulikeit_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(\asyoulikeit_supports('some_unknown_feature'));
    }
}
