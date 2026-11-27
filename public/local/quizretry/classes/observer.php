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

namespace local_quizretry;

/**
 * Event observers that remove practice data when its parent data disappears.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * A course module was deleted: remove practice sessions of that quiz.
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        if (($event->other['modulename'] ?? '') !== 'quiz') {
            return;
        }
        retry_manager::delete_where('cmid = :cmid', ['cmid' => $event->objectid]);
    }

    /**
     * A user was deleted: remove their practice sessions.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        retry_manager::delete_where('userid = :userid', ['userid' => $event->objectid]);
    }

    /**
     * A quiz attempt was deleted (for example by a teacher): remove practice sessions based on it.
     *
     * @param \mod_quiz\event\attempt_deleted $event
     */
    public static function attempt_deleted(\mod_quiz\event\attempt_deleted $event): void {
        retry_manager::delete_where('sourceattemptid = :attemptid', ['attemptid' => $event->objectid]);
    }
}
