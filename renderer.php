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
 * Renderer for mod_asyoulikeit.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renders the entry prompt and the submission list via mustache templates.
 */
class mod_asyoulikeit_renderer extends plugin_renderer_base {
    /**
     * Renders the entry prompt: an "enter" button, or the remaining like count once entered.
     *
     * @param moodle_url $actionurl Where the "enter" form should POST to.
     * @param bool $isentered
     * @param int|null $remaininglikes Null when not yet entered.
     * @return string
     */
    public function entry_prompt(moodle_url $actionurl, bool $isentered, ?int $remaininglikes): string {
        return $this->render_from_template('mod_asyoulikeit/entry_prompt', [
            'isentered' => $isentered,
            'remaininglikes' => $remaininglikes,
            'actionurl' => $actionurl->out(false),
            'sesskey' => sesskey(),
        ]);
    }

    /**
     * Renders the list of submissions for one assignment.
     *
     * @param moodle_url $actionurl Where the like/revoke/visibility forms should POST to.
     * @param array $rows See view.php for the row shape.
     * @return string
     */
    public function submission_list(moodle_url $actionurl, array $rows): string {
        return $this->render_from_template('mod_asyoulikeit/submission_list', [
            'actionurl' => $actionurl->out(false),
            'sesskey' => sesskey(),
            'rows' => $rows,
        ]);
    }

    /**
     * Renders the reviewer report: one row per participant per submission (or a single
     * "not submitted" row for a participant with none).
     *
     * @param array $rows See report.php for the row shape.
     * @return string
     */
    public function report_table(array $rows): string {
        return $this->render_from_template('mod_asyoulikeit/report', [
            'rows' => $rows,
        ]);
    }
}
