<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_wronganswer;

defined('MOODLE_INTERNAL') || die();

use local_wronganswer\local\practice_manager;

/**
 * Cleans plugin-owned practice data when its source quiz attempt is deleted.
 */
class observer {

    /**
     * Remove practice sessions derived from a deleted quiz attempt.
     *
     * @param \mod_quiz\event\attempt_deleted $event
     */
    public static function attempt_deleted(\mod_quiz\event\attempt_deleted $event): void {
        global $DB;

        $sessions = $DB->get_records('local_wronganswer_sess', [
            'quizattemptid' => $event->objectid,
        ]);
        practice_manager::delete_sessions($sessions);
    }
}
