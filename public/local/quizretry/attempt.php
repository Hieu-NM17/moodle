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
 * Practice page: answer (or review) the questions that were wrong in a quiz attempt.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_quizretry\retry_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$id = required_param('id', PARAM_INT);

$retry = retry_manager::get_retry($id);
$cm = get_coursemodule_from_id('quiz', $retry->cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('local/quizretry:use', $context);

$pageurl = new moodle_url('/local/quizretry/attempt.php', ['id' => $retry->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$quiz = $DB->get_record('quiz', ['id' => $retry->quizid], 'id, name, decimalpoints', MUST_EXIST);
$finished = $retry->status === retry_manager::STATUS_FINISHED;
$quba = question_engine::load_questions_usage_by_activity($retry->qubaid);

// Process "Check" buttons, saved answers and the final submit.
if (!$finished && data_submitted() && confirm_sesskey()) {
    try {
        $quba->process_all_actions();
        $submit = optional_param('finishpractice', false, PARAM_BOOL);
        if ($submit) {
            $quba->finish_all_questions();
        }

        $transaction = $DB->start_delegated_transaction();
        question_engine::save_questions_usage_by_activity($quba);
        if ($submit) {
            retry_manager::mark_finished($retry, $quba);
        } else {
            $DB->set_field('local_quizretry', 'timemodified', time(), ['id' => $retry->id]);
        }
        $transaction->allow_commit();
    } catch (question_out_of_sequence_exception $e) {
        throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question', $pageurl);
    }

    $mdlscrollto = optional_param('mdlscrollto', '', PARAM_RAW);
    if ($mdlscrollto !== '' && !$submit) {
        $pageurl->param('mdlscrollto', (int) $mdlscrollto);
    }
    redirect($pageurl);
}

// Page set-up.
$title = get_string('practicetitle', 'local_quizretry', format_string($quiz->name));
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_quizretry'),
        new moodle_url('/local/quizretry/index.php', ['cmid' => $cm->id]));
$PAGE->navbar->add($title);
question_engine::initialise_js();
if (!$finished) {
    $PAGE->requires->js_call_amd('core_question/question_engine', 'initForm', ['#local-quizretry-form']);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Render the questions.
$options = retry_manager::get_display_options($context, $finished, (int) $quiz->decimalpoints);
$questions = [];
$number = 0;
$slots = $quba->get_slots();
foreach ($slots as $slot) {
    $number++;
    $questions[] = ['html' => $quba->render_question($slot, $options, (string) $number)];
}

$data = [
    'finished' => $finished,
    'actionurl' => $pageurl->out(false),
    'sesskey' => sesskey(),
    'questions' => $questions,
    'slots' => implode(',', $slots),
    'finishlabel' => get_string('finishpractice', 'local_quizretry'),
    'historyurl' => (new moodle_url('/local/quizretry/index.php', ['cmid' => $cm->id]))->out(false),
    'historylabel' => get_string('viewhistory', 'local_quizretry'),
    'quizurl' => (new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]))->out(false),
    'quizlabel' => get_string('backtoquiz', 'local_quizretry'),
];

$introkey = $retry->parentid ? 'introfromretry' : 'introfromattempt';
$data['intro'] = get_string($introkey, 'local_quizretry', count($questions));

if ($finished) {
    $data['summary'] = get_string('resultsummary', 'local_quizretry',
            (object) ['correct' => $retry->correctcount, 'total' => $retry->totalquestions]);
    $data['allcorrect'] = $retry->totalquestions > 0 && $retry->correctcount >= $retry->totalquestions;
    if ($data['allcorrect']) {
        $data['allcorrectmsg'] = get_string('practiceallcorrect', 'local_quizretry');
    }

    $stillwrong = count(retry_manager::get_retry_items($quba));
    if ($stillwrong > 0 && retry_manager::is_enabled()) {
        $data['canretry'] = true;
        $data['startaction'] = (new moodle_url('/local/quizretry/start.php'))->out(false);
        $data['fields'] = [['name' => 'retry', 'value' => $retry->id]];
        $data['retrylabel'] = get_string('retrystillwrong', 'local_quizretry', $stillwrong);
    }
    // Link back to the review of the original quiz attempt, if the attempt still exists.
    if ($DB->record_exists('quiz_attempts', ['id' => $retry->sourceattemptid])) {
        $data['reviewurl'] = (new moodle_url('/mod/quiz/review.php',
                ['attempt' => $retry->sourceattemptid]))->out(false);
        $data['reviewlabel'] = get_string('backtoreview', 'local_quizretry');
    }
}

echo $OUTPUT->render_from_template('local_quizretry/practice', $data);
echo $OUTPUT->footer();
