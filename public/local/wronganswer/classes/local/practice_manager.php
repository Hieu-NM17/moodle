<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_wronganswer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates and manages practice sessions independently from official quiz attempts.
 */
class practice_manager {

    /**
     * Find incorrect, partially correct and unanswered automatically gradable questions.
     * Questions waiting for manual grading and non-automatically gradable questions are excluded.
     *
     * The practice session uses the deferredfeedback behaviour, which only supports
     * question types implementing question_automatically_gradable. Filtering here prevents
     * one manually graded question (for example Essay) from breaking the whole session.
     *
     * @param \question_usage_by_activity $usage
     * @return int[] Original slots in the supplied usage.
     */
    public static function get_wrong_slots(\question_usage_by_activity $usage): array {
        $wrongslots = [];

        foreach ($usage->get_slots() as $slot) {
            // Older responses moved aside by Moodle's 'redo question' feature are not current.
            if ($usage->get_question_attempt_metadata($slot, 'originalslot')) {
                continue;
            }

            $question = $usage->get_question($slot);
            if (!($question instanceof \question_automatically_gradable)) {
                continue;
            }

            $questionattempt = $usage->get_question_attempt($slot);
            if (!$questionattempt->has_marks()) {
                continue;
            }

            $state = $questionattempt->get_state();
            $iswrong = $state->is_incorrect() || $state->is_partially_correct() || $state->is_gave_up() ||
                    $state === \question_state::$mangrwrong;

            if ($iswrong) {
                $wrongslots[] = (int)$slot;
            }
        }

        return $wrongslots;
    }

    /**
     * Create a new local practice session using only questions currently answered incorrectly.
     *
     * @param int $userid Current user id.
     * @param int $cmid Course module id.
     * @param int $quizattemptid Original quiz attempt id.
     * @param int $sourceusageid Original or previous practice Question Usage id.
     * @return int New local_wronganswer_sess id.
     */
    public static function create_session(int $userid, int $cmid, int $quizattemptid, int $sourceusageid): int {
        global $DB;

        $context = \context_module::instance($cmid);
        $sourceusage = \question_engine::load_questions_usage_by_activity($sourceusageid);

        if ((int)$sourceusage->get_owning_context()->id !== (int)$context->id ||
                !in_array($sourceusage->get_owning_component(), ['mod_quiz', 'local_wronganswer'], true)) {
            throw new \moodle_exception('invalidpracticeusage', 'local_wronganswer');
        }

        $wrongslots = self::get_wrong_slots($sourceusage);
        if (!$wrongslots) {
            throw new \moodle_exception('nowrongquestions', 'local_wronganswer');
        }

        $transaction = $DB->start_delegated_transaction();

        $practiceusage = \question_engine::make_questions_usage_by_activity('local_wronganswer', $context);
        $practiceusage->set_preferred_behaviour('deferredfeedback');

        foreach ($wrongslots as $sourceslot) {
            $sourcequestionattempt = $sourceusage->get_question_attempt($sourceslot);
            $question = $sourceusage->get_question($sourceslot);
            // This guard is repeated here so a changed source usage cannot create an incompatible session.
            if (!($question instanceof \question_automatically_gradable)) {
                continue;
            }

            $newslot = $practiceusage->add_question($question, $sourcequestionattempt->get_max_mark());

            // Preserve the source variant when supported so that randomised questions remain comparable.
            $variant = (int)$sourcequestionattempt->get_variant();
            $practiceusage->start_question($newslot, max(1, $variant));
        }

        if (!$practiceusage->get_slots()) {
            throw new \moodle_exception('nowrongquestions', 'local_wronganswer');
        }

        \question_engine::save_questions_usage_by_activity($practiceusage);

        $now = time();
        $session = (object)[
            'userid' => $userid,
            'cmid' => $cmid,
            'quizattemptid' => $quizattemptid,
            'sourceusageid' => $sourceusageid,
            'usageid' => (int)$practiceusage->get_id(),
            'status' => 'inprogress',
            'questioncount' => $practiceusage->question_count(),
            'timecreated' => $now,
            'timemodified' => $now,
            'timefinished' => 0,
        ];
        $sessionid = (int)$DB->insert_record('local_wronganswer_sess', $session);

        $transaction->allow_commit();
        return $sessionid;
    }

    /**
     * Submit and grade a practice session. This updates only its own Question Usage.
     *
     * @param \stdClass $session
     * @param \question_usage_by_activity $usage
     * @param array $postdata Raw POST data from the practice form.
     */
    public static function finish_session(\stdClass $session, \question_usage_by_activity $usage, array $postdata): void {
        global $DB;

        if ($session->status !== 'inprogress') {
            throw new \moodle_exception('sessionalreadyfinished', 'local_wronganswer');
        }

        $slots = $usage->get_slots();
        $expectedslots = implode(',', $slots);
        $postedslots = null;
        if (array_key_exists('slots', $postdata) && is_string($postdata['slots'])) {
            $postedslots = clean_param($postdata['slots'], PARAM_SEQUENCE);
        }

        if (!$slots || $postedslots !== $expectedslots) {
            throw new \moodle_exception('invalidpracticeform', 'local_wronganswer');
        }

        // Do not silently mark every question as unanswered when the form did not submit
        // the Question Engine sequence token. A stale or malformed form must be retried.
        foreach ($slots as $slot) {
            if (!$usage->validate_sequence_number($slot, $postdata)) {
                throw new \moodle_exception('invalidpracticeform', 'local_wronganswer');
            }
        }

        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $usage->process_all_actions($now, $postdata);
        $usage->finish_all_questions($now);
        \question_engine::save_questions_usage_by_activity($usage);

        $session->status = 'finished';
        $session->questioncount = $usage->question_count();
        $session->timemodified = $now;
        $session->timefinished = $now;
        $DB->update_record('local_wronganswer_sess', $session);

        $transaction->allow_commit();
    }

    /**
     * Delete every Question Usage owned by this plugin before removing its session records.
     *
     * @param array $sessions Session records.
     */
    public static function delete_sessions(array $sessions): void {
        global $DB;

        if (!$sessions) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($sessions as $session) {
            \question_engine::delete_questions_usage_by_activity((int)$session->usageid);
        }
        $ids = array_map('intval', array_keys($sessions));
        if ($ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_wronganswer_sess', "id $insql", $params);
        }
        $transaction->allow_commit();
    }
}
