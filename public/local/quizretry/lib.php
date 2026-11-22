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
 * Library callbacks for local_quizretry.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve question files (images in question text, feedback, ...) for questions shown in a practice session.
 *
 * Called by question_pluginfile() because the question usage component is local_quizretry.
 *
 * @param stdClass $course course object.
 * @param context $context the context of the file (the question bank context of the question).
 * @param string $component the component the file belongs to (question, qtype_xxx, ...).
 * @param string $filearea the file area.
 * @param int $qubaid the question usage id.
 * @param int $slot the slot in the usage.
 * @param array $args remaining file path parts.
 * @param bool $forcedownload whether to force download.
 * @param array $options additional options for send_stored_file.
 */
function local_quizretry_question_pluginfile($course, $context, $component, $filearea, $qubaid, $slot,
        $args, $forcedownload, array $options = []) {
    global $CFG, $DB, $USER;
    require_once($CFG->libdir . '/questionlib.php');

    $retry = $DB->get_record('local_quizretry', ['qubaid' => $qubaid]);
    if (!$retry || (int) $retry->userid !== (int) $USER->id) {
        send_file_not_found();
    }
    $cm = get_coursemodule_from_id('quiz', $retry->cmid, 0, false);
    if (!$cm) {
        send_file_not_found();
    }
    require_login($cm->course, false, $cm);
    require_capability('local/quizretry:use', context_module::instance($cm->id));

    $quba = question_engine::load_questions_usage_by_activity($qubaid);
    $displayoptions = \local_quizretry\retry_manager::get_display_options(
            context_module::instance($cm->id), $retry->status === \local_quizretry\retry_manager::STATUS_FINISHED);
    if (!$quba->check_file_access($slot, $displayoptions, $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/{$component}/{$filearea}/{$relativepath}";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
