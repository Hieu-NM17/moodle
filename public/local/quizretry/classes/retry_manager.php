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

use context_module;
use mod_quiz\quiz_attempt;
use moodle_exception;
use moodle_url;
use question_bank;
use question_display_options;
use question_engine;
use question_state;
use question_usage_by_activity;
use qubaid_list;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');

/**
 * Business logic for practising the questions answered wrongly in a quiz attempt.
 *
 * The practice is stored as a completely separate question usage (component local_quizretry),
 * so it never touches the quiz attempts, the quiz grade or the gradebook.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retry_manager {
    /** @var string Frankenstyle component name, also used as the question usage component. */
    public const COMPONENT = 'local_quizretry';

    /** @var string Practice is still being worked on. */
    public const STATUS_INPROGRESS = 'inprogress';

    /** @var string Practice has been submitted. */
    public const STATUS_FINISHED = 'finished';

    /**
     * Read a plugin setting, falling back to a default if the setting has never been saved.
     *
     * @param string $name setting name.
     * @param mixed $default default value.
     * @return mixed
     */
    protected static function setting(string $name, $default) {
        $value = get_config(self::COMPONENT, $name);
        return $value === false ? $default : $value;
    }

    /**
     * Is the feature switched on site-wide?
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) self::setting('enabled', 1);
    }

    /**
     * Should partially correct answers be offered for practice too?
     *
     * @return bool
     */
    public static function include_partial(): bool {
        return (bool) self::setting('includepartial', 0);
    }

    /**
     * Must the quiz review options already show the right answers before practice is allowed?
     *
     * @return bool
     */
    public static function require_right_answer(): bool {
        return (bool) self::setting('requirerightanswer', 1);
    }

    /**
     * The question behaviour used for practice sessions.
     *
     * @return string
     */
    public static function get_behaviour(): string {
        $behaviour = (string) self::setting('behaviour', 'immediatefeedback');
        $allowed = ['deferredfeedback', 'immediatefeedback', 'interactive'];
        return in_array($behaviour, $allowed, true) ? $behaviour : 'immediatefeedback';
    }

    /**
     * Does this question state count as "wrong" for practice purposes?
     *
     * Wrong, or left blank (which the question engine records as "gave up").
     * Questions that wait for manual grading, or that a teacher has marked by hand, are never offered.
     *
     * @param question_state $state the state of the question attempt.
     * @param bool $includepartial whether partially correct counts as wrong.
     * @return bool
     */
    public static function is_wrong_state(question_state $state, bool $includepartial): bool {
        if ($state->is_commented()) {
            // A teacher has marked this question by hand, so it is not something to auto-retry.
            return false;
        }
        if ($state->is_incorrect() || $state->is_gave_up()) {
            return true;
        }
        return $includepartial && $state->is_partially_correct();
    }

    /**
     * Work out which questions of a (finished) usage should be practised again.
     *
     * @param question_usage_by_activity $quba the usage of a finished quiz attempt or finished practice.
     * @param bool|null $includepartial null means use the site setting.
     * @return stdClass[] objects with slot, questionid, variant and maxmark.
     */
    public static function get_retry_items(question_usage_by_activity $quba, ?bool $includepartial = null): array {
        $includepartial = $includepartial ?? self::include_partial();
        $items = [];
        foreach ($quba->get_attempt_iterator() as $slot => $qa) {
            $question = $qa->get_question(false);
            if (empty($question->length)) {
                // Descriptions and other information items cannot be answered.
                continue;
            }
            if (!self::is_wrong_state($qa->get_state(), $includepartial)) {
                continue;
            }
            $items[] = (object) [
                'slot' => $slot,
                'questionid' => $question->id,
                'variant' => $qa->get_variant(),
                'maxmark' => $qa->get_max_mark(),
            ];
        }
        return $items;
    }

    /**
     * Check whether the current user may practise the wrong questions of a quiz attempt.
     *
     * The learner can only start a practice if they are already allowed to see which questions
     * were wrong (and, by default, what the right answers are). Otherwise practising would let
     * them bypass the quiz review settings chosen by the teacher.
     *
     * @param quiz_attempt $attemptobj the quiz attempt.
     * @return stdClass with allowed (bool), reason (string, matches an error_* lang string), items and count.
     */
    public static function check_attempt(quiz_attempt $attemptobj): stdClass {
        global $USER;

        $result = (object) ['allowed' => false, 'reason' => '', 'items' => [], 'count' => 0];

        if (!self::is_enabled()) {
            $result->reason = 'disabled';
        } else if ($attemptobj->get_userid() != $USER->id) {
            $result->reason = 'notowner';
        } else if (!$attemptobj->is_finished()) {
            $result->reason = 'notfinished';
        } else if (!has_capability('local/quizretry:use', $attemptobj->get_context())) {
            $result->reason = 'nocapability';
        } else {
            $options = $attemptobj->get_display_options(true);
            if (!$options->attempt || $options->correctness != question_display_options::VISIBLE) {
                $result->reason = 'noreview';
            } else if (self::require_right_answer() && $options->rightanswer != question_display_options::VISIBLE) {
                $result->reason = 'noanswers';
            }
        }
        if ($result->reason !== '') {
            return $result;
        }

        $quba = question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
        $result->items = self::get_retry_items($quba);
        $result->count = count($result->items);
        $result->allowed = true;
        return $result;
    }

    /**
     * Start a practice session from the wrong questions of a finished quiz attempt.
     *
     * @param quiz_attempt $attemptobj the finished quiz attempt (must belong to the current user).
     * @return stdClass the new local_quizretry record.
     */
    public static function create_from_attempt(quiz_attempt $attemptobj): stdClass {
        $check = self::check_attempt($attemptobj);
        if (!$check->allowed) {
            throw new moodle_exception('error_' . $check->reason, self::COMPONENT, $attemptobj->review_url());
        }
        if (empty($check->items)) {
            throw new moodle_exception('nothingtoretry', self::COMPONENT, $attemptobj->review_url());
        }

        $base = (object) [
            'userid' => $attemptobj->get_userid(),
            'courseid' => $attemptobj->get_courseid(),
            'cmid' => $attemptobj->get_cmid(),
            'quizid' => $attemptobj->get_quizid(),
            'sourceattemptid' => $attemptobj->get_attemptid(),
            'parentid' => 0,
        ];
        return self::create_practice($base, $check->items, $attemptobj->get_context(), $attemptobj->review_url());
    }

    /**
     * Start a new practice session from the questions still wrong in a finished practice session.
     *
     * @param stdClass $parent the finished local_quizretry record (must belong to the current user).
     * @return stdClass the new local_quizretry record.
     */
    public static function create_from_retry(stdClass $parent): stdClass {
        global $USER;

        $returnurl = new moodle_url('/local/quizretry/attempt.php', ['id' => $parent->id]);
        $context = context_module::instance($parent->cmid);

        if (!self::is_enabled()) {
            throw new moodle_exception('error_disabled', self::COMPONENT, $returnurl);
        }
        if ($parent->userid != $USER->id) {
            throw new moodle_exception('error_notowner', self::COMPONENT, $returnurl);
        }
        require_capability('local/quizretry:use', $context);
        if ($parent->status !== self::STATUS_FINISHED) {
            throw new moodle_exception('error_notfinished', self::COMPONENT, $returnurl);
        }

        $quba = question_engine::load_questions_usage_by_activity($parent->qubaid);
        $items = self::get_retry_items($quba);
        if (empty($items)) {
            throw new moodle_exception('nothingtoretry', self::COMPONENT, $returnurl);
        }

        $base = (object) [
            'userid' => $parent->userid,
            'courseid' => $parent->courseid,
            'cmid' => $parent->cmid,
            'quizid' => $parent->quizid,
            'sourceattemptid' => $parent->sourceattemptid,
            'parentid' => $parent->id,
        ];
        return self::create_practice($base, $items, $context, $returnurl);
    }

    /**
     * Build the question usage and the practice record.
     *
     * @param stdClass $base fields for the new local_quizretry record.
     * @param stdClass[] $items questions to practise, see {@see get_retry_items()}.
     * @param context_module $context the quiz context, which owns the question usage.
     * @param moodle_url $returnurl where to go if nothing could be loaded.
     * @return stdClass the new record, including its id.
     */
    protected static function create_practice(stdClass $base, array $items, context_module $context,
            moodle_url $returnurl): stdClass {
        global $DB;

        $quba = question_engine::make_questions_usage_by_activity(self::COMPONENT, $context);
        $quba->set_preferred_behaviour(self::get_behaviour());

        $added = 0;
        foreach ($items as $item) {
            try {
                $question = question_bank::load_question($item->questionid);
            } catch (moodle_exception $e) {
                // The question has been removed from the question bank since the attempt.
                continue;
            }
            $slot = $quba->add_question($question, $item->maxmark);
            $variant = max(1, min((int) $item->variant, $question->get_num_variants()));
            $quba->start_question($slot, $variant);
            $added++;
        }
        if ($added === 0) {
            throw new moodle_exception('nothingtoretry', self::COMPONENT, $returnurl);
        }

        $transaction = $DB->start_delegated_transaction();
        question_engine::save_questions_usage_by_activity($quba);

        $now = time();
        $retry = clone $base;
        $retry->qubaid = $quba->get_id();
        $retry->status = self::STATUS_INPROGRESS;
        $retry->totalquestions = $added;
        $retry->correctcount = 0;
        $retry->timecreated = $now;
        $retry->timemodified = $now;
        $retry->timefinished = 0;
        $retry->id = $DB->insert_record('local_quizretry', $retry);
        $transaction->allow_commit();

        return $retry;
    }

    /**
     * Load a practice record that belongs to the current user.
     *
     * @param int $id the local_quizretry id.
     * @return stdClass
     */
    public static function get_retry(int $id): stdClass {
        global $DB, $USER;

        $retry = $DB->get_record('local_quizretry', ['id' => $id], '*', MUST_EXIST);
        if ((int) $retry->userid !== (int) $USER->id) {
            throw new moodle_exception('error_notowner', self::COMPONENT);
        }
        return $retry;
    }

    /**
     * Record that a practice has been submitted and store its score.
     *
     * @param stdClass $retry the local_quizretry record.
     * @param question_usage_by_activity $quba the (already finished) usage.
     */
    public static function mark_finished(stdClass $retry, question_usage_by_activity $quba): void {
        global $DB;

        $total = 0;
        $correct = 0;
        foreach ($quba->get_attempt_iterator() as $qa) {
            $total++;
            if ($qa->get_state()->is_correct()) {
                $correct++;
            }
        }

        $retry->status = self::STATUS_FINISHED;
        $retry->totalquestions = $total;
        $retry->correctcount = $correct;
        $retry->timefinished = time();
        $retry->timemodified = $retry->timefinished;
        $DB->update_record('local_quizretry', $retry);
    }

    /**
     * Practice sessions of a user in one quiz, newest first.
     *
     * @param int $userid user id.
     * @param int $quizid quiz id.
     * @return stdClass[]
     */
    public static function get_user_retries(int $userid, int $quizid): array {
        global $DB;
        return $DB->get_records('local_quizretry', ['userid' => $userid, 'quizid' => $quizid], 'timecreated DESC, id DESC');
    }

    /**
     * Display options used when rendering practice questions.
     *
     * Practice is for learning, so correctness, feedback and right answers are shown; the question
     * behaviour still decides *when* (for example only after "Check" with immediate feedback).
     *
     * @param context_module $context the quiz context.
     * @param bool $finished whether the practice has been submitted (read-only).
     * @param int $decimalpoints decimal places for marks.
     * @return question_display_options
     */
    public static function get_display_options(context_module $context, bool $finished,
            int $decimalpoints = 2): question_display_options {
        $options = new question_display_options();
        $options->context = $context;
        $options->readonly = $finished;
        $options->flags = question_display_options::HIDDEN;
        $options->marks = question_display_options::MARK_AND_MAX;
        $options->markdp = $decimalpoints;
        $options->correctness = question_display_options::VISIBLE;
        $options->feedback = question_display_options::VISIBLE;
        $options->numpartscorrect = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->manualcomment = question_display_options::HIDDEN;
        $options->history = question_display_options::HIDDEN;
        return $options;
    }

    /**
     * Delete practice sessions, including their question usages.
     *
     * @param string $select SQL condition on the local_quizretry table.
     * @param array $params named parameters for the condition.
     */
    public static function delete_where(string $select, array $params): void {
        global $DB;

        $records = $DB->get_records_select('local_quizretry', $select, $params, '', 'id, qubaid');
        if (!$records) {
            return;
        }
        $qubaids = array_map(fn($record) => (int) $record->qubaid, $records);
        question_engine::delete_questions_usage_by_activities(new qubaid_list($qubaids));
        $DB->delete_records_list('local_quizretry', 'id', array_keys($records));
    }
}
