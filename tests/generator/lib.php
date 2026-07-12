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
 * mod_asyoulikeit test data generator.
 *
 * @package     mod_asyoulikeit
 * @copyright   2026 Ken Kobayashi
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generator for mod_asyoulikeit instances, used by both PHPUnit and Behat.
 */
class mod_asyoulikeit_generator extends testing_module_generator {
    /**
     * Creates a mod_asyoulikeit instance, filling in a default name if none was given.
     *
     * @param object|array|null $record
     * @param array|null $options
     * @return object
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        if (!isset($record->name)) {
            $record->name = 'AsYouLikeIt test instance';
        }

        return parent::create_instance($record, $options);
    }
}
