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

use core\hook\output\after_standard_main_region_html_generation;
use mod_quiz\quiz_attempt;
use moodle_url;

/**
 * Hook callbacks: adds a "practise your mistakes" card to quiz pages without touching mod_quiz.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add the card below the main region of the quiz review page and of the quiz view page.
     *
     * @param after_standard_main_region_html_generation $hook
     */
    public static function after_standard_main_region_html_generation(
            after_standard_main_region_html_generation $hook): void {
        global $PAGE, $CFG;

        try {
            if (during_initial_install() || !isloggedin() || isguestuser() || !retry_manager::is_enabled()) {
                return;
            }
            // Note: moodle_page has no __isset, so copy the property before testing it.
            $cm = $PAGE->cm;
            if (!$cm || $cm->modname !== 'quiz') {
                return;
            }
            $pagetype = $PAGE->pagetype;
            if ($pagetype !== 'mod-quiz-review' && $pagetype !== 'mod-quiz-view') {
                return;
            }

            require_once($CFG->dirroot . '/mod/quiz/locallib.php');
            $isreview = $pagetype === 'mod-quiz-review';
            $attemptobj = $isreview ? self::get_review_attempt() : self::get_latest_attempt((int) $cm->instance);
            if (!$attemptobj || $attemptobj->get_cmid() != $cm->id) {
                return;
            }

            $html = self::render_card($hook->renderer, $attemptobj, $isreview);
            if ($html !== '') {
                $hook->add_html($html);
                $PAGE->requires->js_amd_inline(self::get_placement_js($isreview));
            }
        } catch (\Throwable $e) {
            // Never break a quiz page because of this optional feature.
            debugging('local_quizretry: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Small script that moves the card to its place in the quiz page.
     *
     * The quiz pages have no output hook at those spots, so the card is rendered after the main region
     * and moved with JavaScript. Review page: just above the "Finish review" buttons (.submitbtns).
     * View page: right below the list of previous attempts. If the anchor is missing the card stays
     * where it was rendered.
     *
     * @param bool $isreview true on the review page.
     * @return string JavaScript code.
     */
    protected static function get_placement_js(bool $isreview): string {
        $mode = $isreview ? 'review' : 'view';
        return "(function() {
            var place = function() {
                var card = document.querySelector('[data-region=\"local-quizretry-card\"]');
                if (!card) { return; }
                if ('$mode' === 'review') {
                    var btns = document.querySelectorAll('#region-main .submitbtns');
                    var anchor = btns.length ? btns[btns.length - 1] : null;
                    if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(card, anchor); }
                } else {
                    var list = document.querySelector('#region-main ul.list-unstyled.row.row-cols-1.row-cols-md-2') ||
                        document.querySelector('#region-main table.quizattemptsummary');
                    if (list && list.parentNode) { list.parentNode.insertBefore(card, list.nextSibling); }
                }
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', place);
            } else {
                place();
            }
        })();";
    }

    /**
     * The quiz attempt being reviewed on mod/quiz/review.php.
     *
     * @return quiz_attempt|null
     */
    protected static function get_review_attempt(): ?quiz_attempt {
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) {
            return null;
        }
        try {
            return quiz_attempt::create($attemptid);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * The most recent finished attempt of the current user, for mod/quiz/view.php.
     *
     * @param int $quizid quiz id.
     * @return quiz_attempt|null
     */
    protected static function get_latest_attempt(int $quizid): ?quiz_attempt {
        global $USER;

        $attempts = quiz_get_user_attempts($quizid, $USER->id, 'finished', true);
        if (!$attempts) {
            return null;
        }
        $latest = end($attempts);
        return quiz_attempt::create($latest->id);
    }

    /**
     * Render the card.
     *
     * @param \renderer_base $renderer the page renderer.
     * @param quiz_attempt $attemptobj the attempt the card is about.
     * @param bool $isreview true on the review page, false on the quiz view page.
     * @return string HTML, empty if nothing should be shown.
     */
    protected static function render_card(\renderer_base $renderer, quiz_attempt $attemptobj, bool $isreview): string {
        global $DB, $USER;

        $check = retry_manager::check_attempt($attemptobj);
        if (!$check->allowed && $check->reason !== 'noanswers') {
            return '';
        }

        $data = [
            'title' => get_string('cardtitle', 'local_quizretry'),
            'historyurl' => (new moodle_url('/local/quizretry/index.php', ['cmid' => $attemptobj->get_cmid()]))->out(false),
            'historylabel' => get_string('viewhistory', 'local_quizretry'),
            'startaction' => (new moodle_url('/local/quizretry/start.php'))->out(false),
            'sesskey' => sesskey(),
            'fields' => [['name' => 'attempt', 'value' => $attemptobj->get_attemptid()]],
        ];

        if (!$check->allowed) {
            $data['message'] = get_string('cardnoanswers', 'local_quizretry');
        } else {
            $a = (object) ['count' => $check->count, 'attempt' => $attemptobj->get_attempt_number()];
            if ($check->count === 0) {
                $data['message'] = get_string($isreview ? 'cardallcorrect' : 'cardlatestallcorrect', 'local_quizretry', $a);
            } else {
                $data['message'] = get_string($isreview ? 'cardwrongcount' : 'cardlatestwrongcount', 'local_quizretry', $a);
                $data['canstart'] = true;
                $data['startlabel'] = get_string('startretry', 'local_quizretry', $check->count);
            }

            $inprogress = $DB->get_records_select('local_quizretry',
                    'userid = :userid AND sourceattemptid = :attemptid AND status = :status',
                    ['userid' => $USER->id, 'attemptid' => $attemptobj->get_attemptid(),
                            'status' => retry_manager::STATUS_INPROGRESS],
                    'timecreated DESC', 'id', 0, 1);
            if ($inprogress) {
                $data['resumeurl'] = (new moodle_url('/local/quizretry/attempt.php',
                        ['id' => reset($inprogress)->id]))->out(false);
                $data['resumelabel'] = get_string('resumeretry', 'local_quizretry');
            }
        }

        return $renderer->render_from_template('local_quizretry/retry_card', $data);
    }
}
