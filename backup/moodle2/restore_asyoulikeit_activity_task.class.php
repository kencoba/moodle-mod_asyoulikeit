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

/**
 * Restore task for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/asyoulikeit/backup/moodle2/restore_asyoulikeit_stepslib.php');

/**
 * Restore task for one asyoulikeit activity instance.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_asyoulikeit_activity_task extends restore_activity_task {
    /**
     * Defines particular settings for this activity.
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Defines particular steps for this activity.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_asyoulikeit_activity_structure_step('asyoulikeit_structure', 'asyoulikeit.xml'));
    }

    /**
     * Defines the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Defines the decoding rules for links belonging to this activity.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Defines the restore log rules for this activity.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }
}
