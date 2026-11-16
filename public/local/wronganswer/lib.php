<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Serve files embedded in a question rendered within a local_wronganswer practice session.
 *
 * @param stdClass $course Course record.
 * @param context $context Context where the question file is stored.
 * @param string $component File component, normally question or a question type component.
 * @param string $filearea File area.
 * @param int $qubaid Question Usage id.
 * @param int $slot Question slot.
 * @param array $args Remaining file path arguments.
 * @param bool $forcedownload Whether the file must be downloaded.
 * @param array $options Additional file serving options.
 */
function local_wronganswer_question_pluginfile($course, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, array $options = []): void {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    require_once($CFG->dirroot . '/question/engine/lib.php');

    $session = $DB->get_record('local_wronganswer_sess', [
        'usageid' => $qubaid,
        'userid' => $USER->id,
    ], '*', IGNORE_MISSING);
    if (!$session || !in_array($session->status, ['inprogress', 'finished'], true)) {
        send_file_not_found();
    }

    $attempt = $DB->get_record('quiz_attempts', [
        'id' => $session->quizattemptid,
        'userid' => $USER->id,
    ], '*', IGNORE_MISSING);
    if (!$attempt || $attempt->preview || $attempt->state !== 'finished') {
        send_file_not_found();
    }

    $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', IGNORE_MISSING);
    if (!$quiz) {
        send_file_not_found();
    }

    $cm = get_coursemodule_from_id('quiz', $session->cmid, $quiz->course, false, IGNORE_MISSING);
    if (!$cm || (int)$cm->instance !== (int)$quiz->id) {
        send_file_not_found();
    }

    require_login($course, false, $cm);
    $modulecontext = \context_module::instance($cm->id);
    $reviewoptions = quiz_get_review_options($quiz, $attempt, $modulecontext);
    if (empty($reviewoptions->attempt) || empty($reviewoptions->correctness)) {
        send_file_not_found();
    }

    $usage = \question_engine::load_questions_usage_by_activity((int)$qubaid);
    if ($usage->get_owning_component() !== 'local_wronganswer' ||
            (int)$usage->get_owning_context()->id !== (int)$modulecontext->id) {
        send_file_not_found();
    }

    $slot = (int)$slot;
    if (!in_array($slot, $usage->get_slots())) {
        send_file_not_found();
    }

    // question_usage_by_activity::check_file_access() expects display options,
    // unlike mod_quiz\quiz_attempt::check_file_access(), which has a different signature.
    if ($session->status === 'finished') {
        $displayoptions = clone $reviewoptions;
    } else {
        $displayoptions = new question_display_options();
        $displayoptions->readonly = false;
        $displayoptions->hide_all_feedback();
        $displayoptions->marks = question_display_options::HIDDEN;
        $displayoptions->flags = question_display_options::HIDDEN;
    }

    if (!$usage->check_file_access($slot, $displayoptions,
            $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }

    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    $file = get_file_storage()->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
