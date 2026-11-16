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
 * Language strings for local_quizretry (English).
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['backtoquiz'] = 'Back to the quiz';
$string['backtoreview'] = 'Back to attempt review';
$string['cardallcorrect'] = 'Well done! You answered every question in this attempt correctly, so there is nothing to retry.';
$string['cardlatestallcorrect'] = 'You answered every question correctly in your latest attempt (attempt {$a->attempt}).';
$string['cardlatestwrongcount'] = 'In your latest attempt (attempt {$a->attempt}) you answered {$a->count} question(s) incorrectly or left them blank. You can practise just those questions. Your quiz grade will not change.';
$string['cardnoanswers'] = 'Practising your incorrect questions will be available as soon as this quiz allows you to see the correct answers.';
$string['cardtitle'] = 'Practise your mistakes';
$string['cardwrongcount'] = 'In this attempt you answered {$a->count} question(s) incorrectly or left them blank. You can practise just those questions now. Your quiz grade will not change.';
$string['error_disabled'] = 'Retrying incorrect questions is switched off on this site.';
$string['error_noanswers'] = 'Retrying incorrect questions is not available yet because this quiz does not currently show you the correct answers.';
$string['error_nocapability'] = 'You do not have permission to practise incorrect questions in this quiz.';
$string['error_noreview'] = 'Retrying incorrect questions is not available because this quiz does not currently let you review which questions were wrong.';
$string['error_notfinished'] = 'You can only retry questions from a finished attempt.';
$string['error_notowner'] = 'This attempt does not belong to you.';
$string['finishpractice'] = 'Submit practice';
$string['historydate'] = 'Started';
$string['historyempty'] = 'You have not practised any incorrect questions in this quiz yet. Finish an attempt, open its review and choose "Retry incorrect questions".';
$string['historyquestions'] = '{$a} question(s)';
$string['historyresult'] = 'Result';
$string['historyresultvalue'] = '{$a->correct} / {$a->total} correct';
$string['historysource'] = 'Based on';
$string['historysourceattempt'] = 'Quiz attempt #{$a}';
$string['historysourceretry'] = 'Practice #{$a}';
$string['historystatus'] = 'Status';
$string['historytitle'] = 'Practice history: {$a}';
$string['introfromattempt'] = 'Here are the {$a} question(s) you answered incorrectly or left blank. Answer them again; this practice does not change your quiz grade.';
$string['introfromretry'] = 'Here are the {$a} question(s) that were still incorrect in your previous practice. This practice does not change your quiz grade.';
$string['nothingtoretry'] = 'There are no incorrect questions to practise.';
$string['openreview'] = 'Review';
$string['pluginname'] = 'Retry incorrect quiz questions';
$string['practiceallcorrect'] = 'Excellent, you now know all of these questions!';
$string['practicetitle'] = 'Practice: incorrect questions in {$a}';
$string['privacy:metadata:core_question'] = 'The answers given while practising are stored by the question engine.';
$string['privacy:metadata:local_quizretry'] = 'Practice sessions in which a learner redoes questions they answered incorrectly in a quiz attempt.';
$string['privacy:metadata:local_quizretry:correctcount'] = 'How many questions were answered correctly in the practice.';
$string['privacy:metadata:local_quizretry:parentid'] = 'The earlier practice this practice was created from.';
$string['privacy:metadata:local_quizretry:qubaid'] = 'The question usage that stores the practice questions and answers.';
$string['privacy:metadata:local_quizretry:quizid'] = 'The quiz the practice belongs to.';
$string['privacy:metadata:local_quizretry:sourceattemptid'] = 'The quiz attempt the practice is based on.';
$string['privacy:metadata:local_quizretry:status'] = 'Whether the practice is in progress or finished.';
$string['privacy:metadata:local_quizretry:timecreated'] = 'When the practice was started.';
$string['privacy:metadata:local_quizretry:timefinished'] = 'When the practice was submitted.';
$string['privacy:metadata:local_quizretry:totalquestions'] = 'How many questions the practice contains.';
$string['privacy:metadata:local_quizretry:userid'] = 'The user who practised.';
$string['privacy:path'] = 'Quiz practice';
$string['quizretry:use'] = 'Practise incorrect questions of a finished quiz attempt';
$string['resultsummary'] = 'You answered {$a->correct} of {$a->total} question(s) correctly in this practice.';
$string['resumeretry'] = 'Continue practice';
$string['retrystillwrong'] = 'Retry the {$a} question(s) still incorrect';
$string['setting_behaviour'] = 'Question behaviour in practice';
$string['setting_behaviour_desc'] = 'How questions behave while a learner practises. "Immediate feedback" lets the learner check each question straight away.';
$string['setting_enabled'] = 'Enable retrying incorrect questions';
$string['setting_enabled_desc'] = 'Show learners a button on the quiz review page to practise the questions they got wrong.';
$string['setting_includepartial'] = 'Include partially correct questions';
$string['setting_includepartial_desc'] = 'If enabled, questions answered only partially correctly are offered for practice too. Incorrect and blank answers are always included.';
$string['setting_requirerightanswer'] = 'Require right answers to be visible in the quiz review';
$string['setting_requirerightanswer_desc'] = 'If enabled (recommended), learners can only practise once the quiz review options already show them the correct answers. This stops practice from being used to bypass the teacher\'s review settings.';
$string['startretry'] = 'Retry {$a} incorrect question(s)';
$string['statusfinished'] = 'Finished';
$string['statusinprogress'] = 'In progress';
$string['viewhistory'] = 'My practice history';
