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
 * Backup task for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/asyoulikeit/backup/moodle2/backup_asyoulikeit_stepslib.php');

/**
 * Backup task for one asyoulikeit activity instance.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_asyoulikeit_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_asyoulikeit_activity_structure_step('asyoulikeit_structure', 'asyoulikeit.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts as %%WWWROOT%%-relative placeholders.
     * No content field in this activity embeds links to itself, so this is a no-op override
     * (still required: the base class throws if it is not overridden at all).
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
