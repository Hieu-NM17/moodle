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
 * Creates a practice session from the wrong questions of a quiz attempt (or of an earlier practice).
 *
 * Must be called with POST and a valid sesskey, then redirects to attempt.php.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_quizretry\retry_manager;
use mod_quiz\quiz_attempt;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$attemptid = optional_param('attempt', 0, PARAM_INT);
$retryid = optional_param('retry', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/quizretry/start.php'));

if ($attemptid) {
    $attemptobj = quiz_create_attempt_handling_errors($attemptid, null);
    require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
    require_sesskey();
    $retry = retry_manager::create_from_attempt($attemptobj);
} else if ($retryid) {
    $parent = retry_manager::get_retry($retryid);
    $cm = get_coursemodule_from_id('quiz', $parent->cmid, 0, false, MUST_EXIST);
    require_login($cm->course, false, $cm);
    require_sesskey();
    $retry = retry_manager::create_from_retry($parent);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'attempt');
}

redirect(new moodle_url('/local/quizretry/attempt.php', ['id' => $retry->id]));
