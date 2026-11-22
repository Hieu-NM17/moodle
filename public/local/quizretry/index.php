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
 * Lists the practice sessions of the current user in one quiz.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_quizretry\retry_manager;

require_once(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('local/quizretry:use', $context);

$PAGE->set_url(new moodle_url('/local/quizretry/index.php', ['cmid' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('historytitle', 'local_quizretry', format_string($cm->name)));
$PAGE->set_heading($course->fullname);

$retries = retry_manager::get_user_retries($USER->id, $cm->instance);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('historytitle', 'local_quizretry', format_string($cm->name)));

if (!$retries) {
    echo $OUTPUT->notification(get_string('historyempty', 'local_quizretry'), \core\output\notification::NOTIFY_INFO);
} else {
    $table = new html_table();
    $table->head = [
        get_string('historydate', 'local_quizretry'),
        get_string('historysource', 'local_quizretry'),
        get_string('historystatus', 'local_quizretry'),
        get_string('historyresult', 'local_quizretry'),
        '',
    ];
    $table->attributes['class'] = 'generaltable';
    foreach ($retries as $retry) {
        $finished = $retry->status === retry_manager::STATUS_FINISHED;
        $source = $retry->parentid
                ? get_string('historysourceretry', 'local_quizretry', $retry->parentid)
                : get_string('historysourceattempt', 'local_quizretry', $retry->sourceattemptid);
        $result = $finished
                ? get_string('historyresultvalue', 'local_quizretry',
                        (object) ['correct' => $retry->correctcount, 'total' => $retry->totalquestions])
                : get_string('historyquestions', 'local_quizretry', $retry->totalquestions);
        $url = new moodle_url('/local/quizretry/attempt.php', ['id' => $retry->id]);
        $table->data[] = [
            userdate($retry->timecreated),
            $source,
            get_string($finished ? 'statusfinished' : 'statusinprogress', 'local_quizretry'),
            $result,
            html_writer::link($url, get_string($finished ? 'openreview' : 'resumeretry', 'local_quizretry')),
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::div(
    $OUTPUT->single_button(new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]),
            get_string('backtoquiz', 'local_quizretry'), 'get'),
    'mt-3'
);
echo $OUTPUT->footer();
