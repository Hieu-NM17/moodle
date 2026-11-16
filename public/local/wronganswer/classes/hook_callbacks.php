<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_wronganswer;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\after_standard_main_region_html_generation;
use local_wronganswer\local\practice_manager;

/**
 * Injects a practice call-to-action into the quiz review page without editing core.
 */
class hook_callbacks {

    /**
     * Add a link to practise unanswered/incorrect questions after a quiz review.
     *
     * @param after_standard_main_region_html_generation $hook
     */
    public static function after_standard_main_region_html_generation(
            after_standard_main_region_html_generation $hook): void {
        global $DB, $USER;

        $page = $hook->renderer->get_page();
        if (!$page->url || $page->url->get_path() !== '/mod/quiz/review.php') {
            return;
        }

        if (empty($page->context) || $page->context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) {
            return;
        }

        $attempt = $DB->get_record('quiz_attempts', [
            'id' => $attemptid,
            'userid' => $USER->id,
        ], '*', IGNORE_MISSING);

        // Only show the action for a real, submitted attempt owned by this user.
        if (!$attempt || $attempt->preview || $attempt->state !== 'finished') {
            return;
        }

        $cmid = (int)$page->context->instanceid;
        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $cmid, $quiz->course, false, IGNORE_MISSING);
        if (!$cm || (int)$cm->instance !== (int)$quiz->id) {
            return;
        }

        $context = \context_module::instance($cmid);
        $reviewoptions = quiz_get_review_options($quiz, $attempt, $context);

        // Respect both the attempt visibility and correctness review settings.
        if (empty($reviewoptions->attempt) || empty($reviewoptions->correctness)) {
            return;
        }

        try {
            $sourceusage = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
            if (!practice_manager::get_wrong_slots($sourceusage)) {
                return;
            }
        } catch (\Throwable $exception) {
            debugging('Wrong-answer practice could not inspect the source quiz attempt: ' . $exception->getMessage(),
                    DEBUG_DEVELOPER);
            return;
        }

        $url = new \moodle_url('/local/wronganswer/practice.php', ['attemptid' => $attempt->id]);
        $content = \html_writer::tag('p', get_string('practicehint', 'local_wronganswer'));
        $content .= \html_writer::link($url, get_string('retakewrong', 'local_wronganswer'), [
            'class' => 'btn btn-primary',
        ]);
        $hook->add_html(\html_writer::div($content, 'local-wronganswer-cta alert alert-info mt-4'));
    }
}
